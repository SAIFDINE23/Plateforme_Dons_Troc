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
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    private const MAX_MESSAGE_LENGTH = 5000;

    #[Route('/api/mes-conversations', name: 'api_my_conversations', methods: ['GET'])]
    #[Route('/api/conversations', name: 'api_conversations_get', methods: ['GET'])]
    public function listConversations(
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        // Marquer les notifications de messages comme lues
        $messageNotifications = $notificationRepository->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'NEW_MESSAGE')
            ->getQuery()
            ->getResult();

        foreach ($messageNotifications as $notification) {
            $notification->setIsRead(true);
        }

        if ($messageNotifications !== []) {
            $em->flush();
        }

        // Récupérer toutes les conversations de l'utilisateur
        $conversations = $conversationRepository->createQueryBuilder('c')
            ->leftJoin('c.annonce', 'a')
            ->leftJoin('a.images', 'img')
            ->leftJoin('c.participants', 'p')
            ->leftJoin('c.buyer', 'b')
            ->leftJoin('a.owner', 'o')
            ->addSelect('a', 'img', 'p', 'b', 'o')
            ->where('p = :user OR c.buyer = :user OR a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Dédupliquer (un user peut matcher sur plusieurs conditions)
        $seen = [];
        $uniqueConversations = [];
        foreach ($conversations as $conversation) {
            $cid = $conversation->getId()?->toRfc4122();
            if ($cid && !isset($seen[$cid])) {
                $seen[$cid] = true;
                $uniqueConversations[] = $conversation;
            }
        }

        $data = [];
        foreach ($uniqueConversations as $conversation) {
            $annonce = $conversation->getAnnonce();
            $otherUser = $this->getOtherUser($conversation, $user);
            $lastMessage = $messageRepository->createQueryBuilder('m')
                ->where('m.conversation = :conversation')
                ->setParameter('conversation', $conversation)
                ->orderBy('m.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Compter les messages non lus (envoyés par l'autre utilisateur)
            $unreadCount = (int) $messageRepository->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.conversation = :conversation')
                ->andWhere('m.sender != :user')
                ->andWhere('m.isRead = false')
                ->setParameter('conversation', $conversation)
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();

            $image = null;
            if ($annonce && $annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
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
                    'name' => $this->getDisplayName($otherUser),
                ] : null,
                'lastMessage' => $lastMessage ? [
                    'content' => $lastMessage->getContent(),
                    'createdAt' => $lastMessage->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'senderId' => $lastMessage->getSender()?->getId()?->toRfc4122(),
                ] : null,
                'unreadCount' => $unreadCount,
                'updatedAt' => $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/annonces/{id}/conversation', name: 'api_annonce_conversation', methods: ['POST'])]
    public function startConversation(
        string $id,
        AnnonceRepository $annonceRepository,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        /** @var Annonce|null $annonce */
        $annonce = $annonceRepository->find($id);
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
            $conversation = new Conversation();
            $conversation->setBuyer($user);
            $conversation->setAnnonce($annonce);
            $conversation->addParticipant($user);
            $conversation->addParticipant($annonce->getOwner());
            $em->persist($conversation);
        } else {
            // Réparer les participants manquants (conversations héritées pré-migration)
            $this->ensureParticipants($conversation, $user, $annonce->getOwner());
        }

        $em->flush();

        $otherUser = $this->getOtherUser($conversation, $user);

        return $this->json([
            'conversationId' => $conversation->getId()?->toRfc4122(),
            'annonce' => [
                'id' => $annonce->getId()?->toRfc4122(),
                'title' => $annonce->getTitle(),
            ],
            'otherUser' => $otherUser ? [
                'id' => $otherUser->getId()?->toRfc4122(),
                'name' => $this->getDisplayName($otherUser),
            ] : null,
        ], 200);
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

        if (!$this->isParticipant($conversation, $user)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Réparer automatiquement les participants manquants
        $annonce = $conversation->getAnnonce();
        if ($annonce) {
            $this->ensureParticipants($conversation, $conversation->getBuyer(), $annonce->getOwner());
        }

        $messages = $messageRepository->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        $hasChanges = false;
        foreach ($messages as $message) {
            // Marquer comme lu les messages de l'autre utilisateur
            if ($message->getSender()?->getId()?->toRfc4122() !== $user->getId()?->toRfc4122() && !$message->isRead()) {
                $message->setIsRead(true);
                $hasChanges = true;
            }

            $data[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender' => [
                    'id' => $message->getSender()?->getId()?->toRfc4122(),
                    'name' => $message->getSender() ? $this->getDisplayName($message->getSender()) : 'Utilisateur',
                ],
                'createdAt' => $message->getCreatedAt()?->format('Y-m-d H:i:s'),
                'isRead' => $message->isRead(),
                'isMine' => $message->getSender()?->getId()?->toRfc4122() === $user->getId()?->toRfc4122(),
            ];
        }

        if ($hasChanges) {
            $em->flush();
        }

        return $this->json($data);
    }

    #[Route('/api/conversations/{id}/messages', name: 'api_conversations_messages_send', methods: ['POST'])]
    #[Route('/api/conversations/{id}/send', name: 'api_conversations_send', methods: ['POST'])]
    public function sendMessage(
        string $id,
        Request $request,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $em,
        HubInterface $hub,
        LoggerInterface $logger
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

        if (mb_strlen($content) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(['error' => 'Message trop long (max ' . self::MAX_MESSAGE_LENGTH . ' caractères).'], 400);
        }

        $conversation = $conversationRepository->find($id);
        if (!$conversation) {
            return $this->json(['error' => 'Conversation introuvable.'], 404);
        }

        if (!$this->isParticipant($conversation, $user)) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Assainir le contenu (éviter XSS)
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Créer le message
        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $conversation->addMessage($message);
        $conversation->touchUpdatedAt();

        $em->persist($message);

        // Réparer les participants si nécessaire et trouver le destinataire
        $annonce = $conversation->getAnnonce();
        if ($annonce) {
            $this->ensureParticipants($conversation, $conversation->getBuyer(), $annonce->getOwner());
        }

        $recipient = $this->getOtherUser($conversation, $user);

        // Créer la notification pour le destinataire
        if ($recipient) {
            $notification = new Notification();
            $notification->setUser($recipient);
            $notification->setType('NEW_MESSAGE');
            $notification->setMessage('Nouveau message de ' . $this->getDisplayName($user) . ' sur: ' . ($annonce?->getTitle() ?? 'une annonce'));
            $notification->setLink('/mes-messages/' . $conversation->getId()?->toRfc4122());
            $em->persist($notification);
        }

        $em->flush();

        // Publier via Mercure (en mode best-effort, ne doit pas bloquer l'envoi)
        $this->publishMercureUpdate($hub, $logger, $conversation, $message, $user, $recipient);

        return $this->json([
            'status' => 'ok',
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender' => [
                    'id' => $user->getId()?->toRfc4122(),
                    'name' => $this->getDisplayName($user),
                ],
                'createdAt' => $message->getCreatedAt()?->format('Y-m-d H:i:s'),
                'isMine' => true,
            ],
        ], 201);
    }

    #[Route('/api/conversations/new', name: 'api_conversations_new', methods: ['POST'])]
    public function createConversationWithMessage(
        Request $request,
        AnnonceRepository $annonceRepository,
        ConversationRepository $conversationRepository,
        EntityManagerInterface $em,
        HubInterface $hub,
        LoggerInterface $logger
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

        if (mb_strlen($content) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(['error' => 'Message trop long (max ' . self::MAX_MESSAGE_LENGTH . ' caractères).'], 400);
        }

        /** @var Annonce|null $annonce */
        $annonce = $annonceRepository->find($annonceId);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        $owner = $annonce->getOwner();
        if ($owner?->getId()?->toRfc4122() === $user->getId()?->toRfc4122()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous contacter vous-même.'], 400);
        }

        $conversation = $conversationRepository->findOneBy([
            'buyer' => $user,
            'annonce' => $annonce,
        ]);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setBuyer($user);
            $conversation->setAnnonce($annonce);
            $conversation->addParticipant($user);
            $conversation->addParticipant($owner);
            $em->persist($conversation);
        } else {
            // Réparer les participants manquants
            $this->ensureParticipants($conversation, $user, $owner);
        }

        // Assainir le contenu
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($content);
        $conversation->addMessage($message);
        $conversation->touchUpdatedAt();

        $em->persist($message);

        // Notification au destinataire (le owner de l'annonce)
        $recipient = $this->getOtherUser($conversation, $user);
        if ($recipient) {
            $notification = new Notification();
            $notification->setUser($recipient);
            $notification->setType('NEW_MESSAGE');
            $notification->setMessage('Nouveau message de ' . $this->getDisplayName($user) . ' sur: ' . $annonce->getTitle());
            $notification->setLink('/mes-messages/' . $conversation->getId()?->toRfc4122());
            $em->persist($notification);
        }

        $em->flush();

        // Publier via Mercure (best-effort)
        $this->publishMercureUpdate($hub, $logger, $conversation, $message, $user, $recipient);

        return $this->json([
            'conversationId' => $conversation->getId()?->toRfc4122(),
        ], 201);
    }

    // ─── Helpers privés ──────────────────────────────────────────

    /**
     * Publie les mises à jour Mercure en mode best-effort.
     * Si Mercure n'est pas disponible, on log l'erreur mais on ne crash pas.
     */
    private function publishMercureUpdate(
        HubInterface $hub,
        LoggerInterface $logger,
        Conversation $conversation,
        Message $message,
        User $sender,
        ?User $recipient
    ): void {
        $conversationId = $conversation->getId()?->toRfc4122();
        if (!$conversationId) {
            return;
        }

        try {
            $messagePayload = json_encode([
                'type' => 'message',
                'conversationId' => $conversationId,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => [
                        'id' => $sender->getId()?->toRfc4122(),
                        'name' => $this->getDisplayName($sender),
                    ],
                    'createdAt' => $message->getCreatedAt()?->format('Y-m-d H:i:s'),
                ],
            ], JSON_THROW_ON_ERROR);

            $hub->publish(new Update(
                '/conversations/' . $conversationId,
                $messagePayload
            ));

            $conversationPayload = json_encode([
                'type' => 'conversation_updated',
                'conversationId' => $conversationId,
            ], JSON_THROW_ON_ERROR);

            foreach ([$sender, $recipient] as $participant) {
                if ($participant instanceof User) {
                    $hub->publish(new Update(
                        '/users/' . $participant->getId()?->toRfc4122() . '/conversations',
                        $conversationPayload
                    ));
                }
            }
        } catch (\Throwable $e) {
            $logger->warning('Mercure publish failed: ' . $e->getMessage(), [
                'conversationId' => $conversationId,
            ]);
        }
    }

    /**
     * S'assure que les deux utilisateurs sont dans la liste des participants.
     * Corrige les conversations héritées pré-migration participants.
     */
    private function ensureParticipants(Conversation $conversation, ?User $userA, ?User $userB): void
    {
        $existingIds = [];
        foreach ($conversation->getParticipants() as $p) {
            $existingIds[$p->getId()?->toRfc4122()] = true;
        }

        if ($userA && !isset($existingIds[$userA->getId()?->toRfc4122()])) {
            $conversation->addParticipant($userA);
        }
        if ($userB && !isset($existingIds[$userB->getId()?->toRfc4122()])) {
            $conversation->addParticipant($userB);
        }
    }

    /**
     * Vérifie si l'utilisateur fait partie de la conversation.
     */
    private function isParticipant(Conversation $conversation, User $user): bool
    {
        $userId = $user->getId()?->toRfc4122();

        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId()?->toRfc4122() === $userId) {
                return true;
            }
        }

        if ($conversation->getBuyer()?->getId()?->toRfc4122() === $userId) {
            return true;
        }

        $annonce = $conversation->getAnnonce();
        if ($annonce?->getOwner()?->getId()?->toRfc4122() === $userId) {
            return true;
        }

        return false;
    }

    /**
     * Trouve l'autre utilisateur dans la conversation.
     */
    private function getOtherUser(Conversation $conversation, User $user): ?User
    {
        $userId = $user->getId()?->toRfc4122();

        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId()?->toRfc4122() !== $userId) {
                return $participant;
            }
        }

        // Fallback sur buyer / owner
        $annonce = $conversation->getAnnonce();
        $owner = $annonce?->getOwner();
        if ($owner && $owner->getId()?->toRfc4122() !== $userId) {
            return $owner;
        }

        $buyer = $conversation->getBuyer();
        if ($buyer && $buyer->getId()?->toRfc4122() !== $userId) {
            return $buyer;
        }

        return null;
    }

    private function getDisplayName(User $user): string
    {
        $alias = $user->getAlias();
        if ($alias !== null && trim($alias) !== '') {
            return $alias;
        }

        return (string) $user->getCasUid();
    }
}