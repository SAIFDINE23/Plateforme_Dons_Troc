<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\AnnonceRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatApiController extends AbstractController
{
    #[Route('/api/conversations', name: 'api_conversations_get', methods: ['GET'])]
    public function listConversations(ConversationRepository $conversationRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $conversations = $conversationRepository->createQueryBuilder('c')
            ->leftJoin('c.annonce', 'a')
            ->leftJoin('a.images', 'img')
            ->leftJoin('c.participants', 'p')
            ->addSelect('a', 'img')
            ->andWhere('p = :user OR c.buyer = :user OR a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($conversations as $conversation) {
            $annonce = $conversation->getAnnonce();
            $lastMessage = $conversation->getMessages()->last() ?: null;

            $image = null;
            if ($annonce && $annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
            }

            $otherUser = null;
            foreach ($conversation->getParticipants() as $participant) {
                if ($participant->getId()?->toRfc4122() !== $user->getId()?->toRfc4122()) {
                    $otherUser = $participant;
                    break;
                }
            }
            if (!$otherUser && $annonce) {
                $otherUser = $annonce->getOwner();
                if ($otherUser && $otherUser->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
                    $otherUser = $conversation->getBuyer();
                }
            }

            $data[] = [
                'id' => $conversation->getId()?->toRfc4122(),
                'annonce' => [
                    'id' => $annonce?->getId()?->toRfc4122(),
                    'title' => $annonce?->getTitle(),
                    'image' => $image,
                ],
                'otherUser' => $otherUser ? [
                    'id' => $otherUser->getId()?->toRfc4122(),
                    'name' => $otherUser->getCasUid(),
                ] : null,
                'lastMessage' => $lastMessage ? [
                    'content' => $lastMessage->getContent(),
                    'createdAt' => $lastMessage->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'senderId' => $lastMessage->getSender()?->getId()?->toRfc4122(),
                ] : null,
                'updatedAt' => $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/conversations/{id}/messages', name: 'api_conversations_messages', methods: ['GET'])]
    public function listMessages(
        string $id,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $conversation = $conversationRepository->find($id);
        if (!$conversation) {
            return $this->json(['error' => 'Conversation introuvable.'], 404);
        }

        $annonce = $conversation->getAnnonce();
        $isParticipant = false;
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
                $isParticipant = true;
                break;
            }
        }
        if (!$isParticipant) {
            $isParticipant = $conversation->getBuyer()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122()
                || $annonce?->getOwner()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122();
        }

        if (!$isParticipant) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $messages = $messageRepository->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($messages as $message) {
            if ($message->getSender()?->getId()?->toRfc4122() !== $user->getId()?->toRfc4122() && !$message->isRead()) {
                $message->setIsRead(true);
            }

            $data[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender' => [
                    'id' => $message->getSender()?->getId()?->toRfc4122(),
                    'name' => $message->getSender()?->getCasUid(),
                ],
                'createdAt' => $message->getCreatedAt()?->format('Y-m-d H:i:s'),
                'isRead' => $message->isRead(),
                'isMine' => $message->getSender()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122(),
            ];
        }

        $em->flush();

        return $this->json($data);
    }

    #[Route('/api/conversations/new', name: 'api_conversations_new', methods: ['POST'])]
    public function createConversation(
        Request $request,
        AnnonceRepository $annonceRepository,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $annonceId = $payload['annonceId'] ?? null;
        $content = trim((string) ($payload['content'] ?? ''));

        if (!$annonceId || $content === '') {
            return $this->json(['error' => 'Annonce et message requis.'], 400);
        }

        $annonce = $annonceRepository->find($annonceId);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        if ($annonce->getOwner()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous contacter vous-même.'], 400);
        }

        $conversation = $conversationRepository->findOneBy([
            'buyer' => $user,
            'annonce' => $annonce,
        ]);

        if (!$conversation) {
            $participants = [$user, $annonce->getOwner()];
            $conversation = $conversationRepository->createQueryBuilder('c')
                ->leftJoin('c.participants', 'p')
                ->andWhere('c.annonce = :annonce')
                ->andWhere('p IN (:participants)')
                ->setParameter('annonce', $annonce)
                ->setParameter('participants', $participants)
                ->groupBy('c.id')
                ->having('COUNT(DISTINCT p.id) = 2')
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setBuyer($user);
            $conversation->setAnnonce($annonce);
            $conversation->addParticipant($user);
            $conversation->addParticipant($annonce->getOwner());
        } elseif ($conversation->getParticipants()->count() === 0) {
            $conversation->addParticipant($user);
            $conversation->addParticipant($annonce->getOwner());
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);

        $conversation->addMessage($message);
        $conversation->touchUpdatedAt();

        $em->persist($conversation);
        $em->persist($message);

        $notification = new Notification();
        $notification->setUser($annonce->getOwner());
        $notification->setType('NEW_MESSAGE');
        $notification->setMessage('Nouveau message au sujet de: ' . $annonce->getTitle());
        $notification->setLink('/messages');
        $em->persist($notification);

        $em->flush();

        return $this->json([
            'conversationId' => $conversation->getId()?->toRfc4122(),
        ], 201);
    }

    #[Route('/api/conversations/{id}/send', name: 'api_conversations_send', methods: ['POST'])]
    public function sendMessage(
        string $id,
        Request $request,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $content = trim((string) ($payload['content'] ?? ''));

        if ($content === '') {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        $conversation = $conversationRepository->find($id);
        if (!$conversation) {
            return $this->json(['error' => 'Conversation introuvable.'], 404);
        }

        $annonce = $conversation->getAnnonce();
        $isParticipant = false;
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
                $isParticipant = true;
                break;
            }
        }
        if (!$isParticipant) {
            $isParticipant = $conversation->getBuyer()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122()
                || $annonce?->getOwner()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122();
        }

        if (!$isParticipant) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $conversation->addMessage($message);
        $conversation->touchUpdatedAt();

        $em->persist($message);

        $recipient = null;
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId()?->toRfc4122() !== $user->getId()?->toRfc4122()) {
                $recipient = $participant;
                break;
            }
        }
        if (!$recipient) {
            $recipient = $conversation->getBuyer();
            if ($recipient && $recipient->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
                $recipient = $annonce?->getOwner();
            }
        }

        if ($recipient) {
            $notification = new Notification();
            $notification->setUser($recipient);
            $notification->setType('NEW_MESSAGE');
            $notification->setMessage('Nouveau message sur: ' . $annonce?->getTitle());
            $notification->setLink('/messages');
            $em->persist($notification);
        }

        $em->flush();

        return $this->json([
            'status' => 'ok',
        ], 201);
    }
}
