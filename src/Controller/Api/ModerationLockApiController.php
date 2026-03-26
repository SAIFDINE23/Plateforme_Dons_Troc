<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\User;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant le verrouillage pessimiste des annonces pour la modération
 */
#[Route('/api/moderation/annonce')]
class ModerationLockApiController extends AbstractController
{
    /**
     * POST /api/moderation/annonce/{id}/lock
     * Verrouille une annonce pour la modération
     * 
     * Un modérateur peut verrouiller une annonce pour empêcher d'autres modérateurs
     * de la traiter simultanément. Le verrou expire automatiquement après 30 minutes.
     */
    #[Route('/{id}/lock', name: 'api_moderation_lock', methods: ['POST'])]
    public function lock(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier que l'utilisateur est au moins MODERATOR
        if (!$this->isGranted('ROLE_MODERATOR')) {
            return $this->json([
                'error' => 'Accès refusé. Vous devez être modérateur pour verrouiller une annonce.'
            ], 403);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Récupérer l'annonce
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée.'], 404);
        }

        // Vérifier si l'annonce est déjà verrouillée
        if ($annonce->isLocked()) {
            // Si le verrou a expiré (> 30 minutes), on le libère automatiquement
            if ($annonce->isLockExpired()) {
                $annonce->unlock();
                $em->flush();
            } 
            // Si verrouillé par le même utilisateur, on considère que c'est un renouvellement
            elseif ($annonce->isLockedBy($currentUser)) {
                // Renouveler le verrou
                $annonce->lock($currentUser);
                $em->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Verrou renouvelé avec succès.',
                    'locked_by' => $currentUser->getCasUid(),
                    'locked_at' => $annonce->getLockedAt()?->format('Y-m-d H:i:s'),
                ]);
            }
            // Sinon, l'annonce est verrouillée par quelqu'un d'autre
            else {
                $lockedBy = $annonce->getLockedBy();
                $lockedByName = $lockedBy?->getCasUid() ?? 'un autre modérateur';
                $roles = $lockedBy?->getRoles() ?? [];
                $isResponsable = in_array('ROLE_RESPONSABLE', $roles, true);
                $role = $isResponsable ? 'responsable' : 'modérateur';
                
                return $this->json([
                    'success' => false,
                    'message' => "Cette annonce est actuellement traitée par {$role} {$lockedByName}. Veuillez réessayer plus tard.",
                    'locked_by' => $lockedBy?->getCasUid(),
                    'locked_by_email' => $lockedBy?->getEmail(),
                    'locked_at' => $annonce->getLockedAt()?->format('d/m/Y à H:i'),
                    'time_remaining' => 30 - ((new \DateTimeImmutable())->getTimestamp() - $annonce->getLockedAt()->getTimestamp()) / 60,
                ], 423); // 423 Locked
            }
        }

        // Verrouiller l'annonce
        $annonce->lock($currentUser);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Annonce verrouillée avec succès.',
            'locked_by' => $currentUser->getCasUid(),
            'locked_at' => $annonce->getLockedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * POST /api/moderation/annonce/{id}/unlock
     * Déverrouille manuellement une annonce
     * 
     * Permet au modérateur qui a verrouillé l'annonce de la déverrouiller manuellement
     * (par exemple s'il abandonne la modération). Un RESPONSABLE peut déverrouiller
     * n'importe quelle annonce.
     */
    #[Route('/{id}/unlock', name: 'api_moderation_unlock', methods: ['POST'])]
    public function unlock(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier que l'utilisateur est au moins MODERATOR
        if (!$this->isGranted('ROLE_MODERATOR')) {
            return $this->json([
                'error' => 'Accès refusé.'
            ], 403);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Récupérer l'annonce
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée.'], 404);
        }

        // Vérifier si l'annonce est verrouillée
        if (!$annonce->isLocked()) {
            return $this->json([
                'error' => 'Cette annonce n\'est pas verrouillée.'
            ], 400);
        }

        // Seul celui qui a verrouillé ou un RESPONSABLE peut déverrouiller
        if (!$annonce->isLockedBy($currentUser) && !$this->isGranted('ROLE_RESPONSABLE')) {
            return $this->json([
                'error' => 'Vous n\'êtes pas autorisé à déverrouiller cette annonce.',
                'locked_by' => $annonce->getLockedBy()?->getCasUid(),
            ], 403);
        }

        // Déverrouiller l'annonce
        $annonce->unlock();
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Annonce déverrouillée avec succès.',
        ]);
    }

    /**
     * GET /api/moderation/annonce/{id}/lock-status
     * Vérifie le statut de verrouillage d'une annonce
     */
    #[Route('/{id}/lock-status', name: 'api_moderation_lock_status', methods: ['GET'])]
    public function lockStatus(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier que l'utilisateur est au moins MODERATOR
        if (!$this->isGranted('ROLE_MODERATOR')) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Récupérer l'annonce
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée.'], 404);
        }

        // Libérer automatiquement si le verrou a expiré
        if ($annonce->isLocked() && $annonce->isLockExpired()) {
            $annonce->unlock();
            $em->flush();
        }

        if (!$annonce->isLocked()) {
            return $this->json([
                'is_locked' => false,
                'can_moderate' => true,
            ]);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        return $this->json([
            'is_locked' => true,
            'locked_by' => $annonce->getLockedBy()?->getCasUid(),
            'locked_by_email' => $annonce->getLockedBy()?->getEmail(),
            'locked_at' => $annonce->getLockedAt()?->format('Y-m-d H:i:s'),
            'is_locked_by_me' => $annonce->isLockedBy($currentUser),
            'can_moderate' => $annonce->isLockedBy($currentUser) || $this->isGranted('ROLE_RESPONSABLE'),
            'lock_expires_in' => $this->getLockExpiresIn($annonce),
        ]);
    }

    /**
     * Calcule le temps restant avant expiration du verrou (en secondes)
     */
    private function getLockExpiresIn(Annonce $annonce): ?int
    {
        if (!$annonce->isLocked()) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $lockDuration = $now->getTimestamp() - $annonce->getLockedAt()->getTimestamp();
        $remaining = 1800 - $lockDuration; // 30 minutes = 1800 secondes

        return max(0, $remaining);
    }
}
