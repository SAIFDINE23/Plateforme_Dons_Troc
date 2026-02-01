<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationApiController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications_get', methods: ['GET'])]
    public function getNotifications(NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $notifications = $notificationRepository->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(static function (Notification $notification): array {
            $link = $notification->getLink();
            if (is_string($link) && $link !== '') {
                $link = trim($link);
                $parsedPath = parse_url($link, PHP_URL_PATH) ?? $link;
                $normalizedPath = preg_replace('#^/annonces/#', '/annonce/', rtrim($parsedPath, '/'));
                $link = $normalizedPath;
            }
            return [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'link' => $link,
                'createdAt' => $notification->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return $this->json($data);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notifications_read', methods: ['PATCH'])]
    public function markAsRead(
        int $id,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $notification = $notificationRepository->find($id);
        if (!$notification) {
            return $this->json(['error' => 'Notification introuvable.'], 404);
        }

        if ($notification->getUser()?->getId()?->toRfc4122() !== $user->getId()?->toRfc4122()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->json(['status' => 'ok']);
    }
}
