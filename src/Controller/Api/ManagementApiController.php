<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\AnnonceState;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ManagementApiController extends AbstractController
{
    /**
     * GET /api/my/annonces
     * Récupère toutes les annonces de l'utilisateur connecté
     */
    #[Route('/api/my/annonces', name: 'api_my_annonces', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMyAnnonces(AnnonceRepository $annonceRepository): JsonResponse
    {
        $user = $this->getUser();
        
        // Récupérer les annonces de l'utilisateur
        $annonces = $annonceRepository->createQueryBuilder('a')
            ->leftJoin('a.images', 'img')
            ->addSelect('img')
            ->where('a.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Formatter les données
        $data = [];
        foreach ($annonces as $annonce) {
            $images = [];
            if ($annonce->getImages()->count() > 0) {
                foreach ($annonce->getImages() as $annonceImage) {
                    $images[] = '/uploads/annonces/' . $annonceImage->getImageName();
                }
            }
            $image = $images[0] ?? null;

            $data[] = [
                'id' => $annonce->getId()->toRfc4122(),
                'title' => $annonce->getTitle(),
                'status' => $annonce->getState()->value,
                'date' => $annonce->getCreatedAt()->format('d/m/Y'),
                'image' => $image,
                'images' => $images,
                'refusalReason' => $annonce->getRefusalReason(),
            ];
        }

        return $this->json($data);
    }

    /**
     * GET /api/admin/pending
     * Récupère les annonces en attente de modération
     * - Admin global : toutes les annonces PENDING_REVIEW
     * - Modérateur local : uniquement celles de son campus
     */
    #[Route('/api/admin/pending', name: 'api_admin_pending', methods: ['GET'])]
    public function getPendingAnnonces(AnnonceRepository $annonceRepository): JsonResponse
    {
        // Vérifier que l'utilisateur est au moins MODERATOR
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        
        // Tous les modérateurs et responsables peuvent voir toutes les annonces
        $qb = $annonceRepository->createQueryBuilder('a')
            ->leftJoin('a.owner', 'u')
            ->leftJoin('a.images', 'img')
            ->leftJoin('a.category', 'c')
            ->addSelect('u', 'img', 'c')
            ->where('a.state = :state')
            ->setParameter('state', AnnonceState::PENDING_REVIEW)
            ->orderBy('a.createdAt', 'ASC');

        $annonces = $qb->getQuery()->getResult();

        // Formatter les données
        $data = [];
        foreach ($annonces as $annonce) {
            $image = null;
            if ($annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
            }

            $data[] = [
                'id' => $annonce->getId()->toRfc4122(),
                'title' => mb_convert_encoding($annonce->getTitle(), 'UTF-8', 'UTF-8'),
                'description' => mb_convert_encoding(substr($annonce->getDescription(), 0, 150), 'UTF-8', 'UTF-8') . '...',
                'owner' => $annonce->getOwner()->getCasUid(),
                'campuses' => $annonce->getCampuses(),
                'category' => $annonce->getCategory() ? [
                    'id' => $annonce->getCategory()->getId(),
                    'name' => mb_convert_encoding($annonce->getCategory()->getName(), 'UTF-8', 'UTF-8')
                ] : null,
                'customCategoryName' => $annonce->getCustomCategoryName(),
                'date' => $annonce->getCreatedAt()->format('d/m/Y H:i'),
                'image' => $image,
            ];
        }

        return $this->json($data);
    }

    /**
     * GET /api/admin/annonce/{id}
     * Détails d'une annonce en attente de modération
     */
    #[Route('/api/admin/annonce/{id}', name: 'api_admin_annonce_show', methods: ['GET'])]
    public function getPendingAnnonce(
        string $id,
        AnnonceRepository $annonceRepository
    ): JsonResponse {
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $user = $this->getUser();

        $annonce = $annonceRepository->createQueryBuilder('a')
            ->leftJoin('a.owner', 'u')
            ->leftJoin('a.images', 'img')
            ->leftJoin('a.category', 'c')
            ->addSelect('u', 'img', 'c')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée'], 404);
        }

        if ($annonce->getState() !== AnnonceState::PENDING_REVIEW) {
            return $this->json(['error' => 'Annonce non disponible pour modération'], 400);
        }

        // Tous les modérateurs peuvent modérer toutes les annonces

        $images = [];
        foreach ($annonce->getImages() as $image) {
            $images[] = '/uploads/annonces/' . $image->getImageName();
        }

        $data = [
            'id' => $annonce->getId()->toRfc4122(),
            'title' => $annonce->getTitle(),
            'description' => $annonce->getDescription(),
            'campuses' => $annonce->getCampuses(),
            'type' => $annonce->getType()->value,
            'price' => $annonce->getType()->value === 'DON' ? 'Gratuit' : 'Troc',
            'category' => $annonce->getCategory()?->getName(),
            'customCategoryName' => $annonce->getCustomCategoryName(),
            'state' => $annonce->getState()->value,
            'owner' => [
                'cas_uid' => $annonce->getOwner()->getCasUid(),
                'email' => $annonce->getOwner()->getEmail(),
            ],
            'createdAt' => $annonce->getCreatedAt()->format('Y-m-d H:i:s'),
            'images' => $images,
        ];

        return $this->json($data);
    }

    /**
     * POST /api/admin/annonce/{id}/decide
     * Valide ou refuse une annonce
     * Body : { "action": "validate" | "reject" }
     */
    #[Route('/api/admin/annonce/{id}/decide', name: 'api_admin_decide', methods: ['POST'])]
    public function decideAnnonce(
        string $id,
        Request $request,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Vérifier que l'utilisateur est au moins MODERATOR
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        $user = $this->getUser();
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Récupérer l'annonce
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée'], 404);
        }

        // SÉCURITÉ : Vérifier le verrouillage pessimiste
        // Libérer automatiquement si le verrou a expiré (> 30 minutes)
        if ($annonce->isLocked() && $annonce->isLockExpired()) {
            $annonce->unlock();
            $em->flush();
        }

        // Vérifier que seul celui qui a verrouillé (ou un RESPONSABLE) peut valider/refuser
        if ($annonce->isLocked()) {
            if (!$annonce->isLockedBy($currentUser) && !$this->isGranted('ROLE_RESPONSABLE')) {
                $lockedBy = $annonce->getLockedBy();
                $lockedByName = $lockedBy?->getCasUid() ?? 'un autre modérateur';
                
                return $this->json([
                    'success' => false,
                    'message' => "Cette annonce est actuellement gérée par {$lockedByName}. Veuillez patienter qu'il termine ou choisir une autre annonce.",
                    'locked_by' => $lockedBy?->getCasUid(),
                    'locked_by_email' => $lockedBy?->getEmail(),
                    'locked_at' => $annonce->getLockedAt()?->format('d/m/Y à H:i'),
                ], 423); // 423 Locked
            }
        }

        // Récupérer l'action
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? null;
        $reason = $data['reason'] ?? '';

        if ($action === 'validate') {
            $annonce->setState(AnnonceState::PUBLISHED);
            $annonce->setRefusalReason(null); // Vider le motif au cas où
            $owner = $annonce->getOwner();
            if ($owner instanceof User) {
                $notification = new Notification();
                $notification->setUser($owner);
                $notification->setType('VALIDATION');
                $notification->setMessage('Votre annonce "' . $annonce->getTitle() . '" est en ligne.');
                $notification->setLink('/annonce/' . $annonce->getId()?->toRfc4122());
                $em->persist($notification);
            }
        } elseif ($action === 'reject') {
            // Vérifier que le motif est fourni
            if (empty(trim($reason))) {
                return $this->json(['error' => 'Le motif du refus est obligatoire'], 400);
            }
            $annonce->setState(AnnonceState::REJECTED);
            $annonce->setRefusalReason($reason);
            $owner = $annonce->getOwner();
            if ($owner instanceof User) {
                $notification = new Notification();
                $notification->setUser($owner);
                $notification->setType('REFUSAL');
                $notification->setMessage('Votre annonce "' . $annonce->getTitle() . '" a été refusée : ' . $reason);
                $notification->setLink('/annonce/' . $annonce->getId()?->toRfc4122());
                $em->persist($notification);
            }
        } else {
            return $this->json(['error' => 'Action invalide'], 400);
        }

        // AUTOMATIQUEMENT déverrouiller l'annonce après validation/refus
        $annonce->unlock();

        $em->persist($annonce);
        $em->flush();

        return $this->json([
            'message' => 'Annonce ' . ($action === 'validate' ? 'validée' : 'refusée') . ' avec succès',
            'annonceId' => $id,
            'newState' => $annonce->getState()->value
        ]);
    }
}
