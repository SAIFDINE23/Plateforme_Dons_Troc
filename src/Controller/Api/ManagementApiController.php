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
            $image = null;
            if ($annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
            }

            $data[] = [
                'id' => $annonce->getId()->toRfc4122(),
                'title' => $annonce->getTitle(),
                'status' => $annonce->getState()->value,
                'date' => $annonce->getCreatedAt()->format('d/m/Y'),
                'image' => $image,
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
        // Vérifier que l'utilisateur a ROLE_ADMIN ou ROLE_MODERATOR
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $user = $this->getUser();
        
        $qb = $annonceRepository->createQueryBuilder('a')
            ->leftJoin('a.owner', 'u')
            ->leftJoin('a.images', 'img')
            ->addSelect('u', 'img')
            ->where('a.state = :state')
            ->setParameter('state', AnnonceState::PENDING_REVIEW)
            ->orderBy('a.createdAt', 'ASC');

        // Si modérateur local (non-admin), filtrer par campus
        if (!$this->isGranted('ROLE_ADMIN') && $user->getModeratedCampus()) {
            $qb->andWhere('a.campus = :campus')
                ->setParameter('campus', $user->getModeratedCampus());
        }

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
                'campus' => $annonce->getCampus()->value,
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
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
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

        if (!$this->isGranted('ROLE_ADMIN') && $user && method_exists($user, 'getModeratedCampus')) {
            if ($user->getModeratedCampus() && $annonce->getCampus() !== $user->getModeratedCampus()) {
                return $this->json(['error' => 'Vous ne pouvez pas modérer cette annonce'], 403);
            }
        }

        $images = [];
        foreach ($annonce->getImages() as $image) {
            $images[] = '/uploads/annonces/' . $image->getImageName();
        }

        $data = [
            'id' => $annonce->getId()->toRfc4122(),
            'title' => $annonce->getTitle(),
            'description' => $annonce->getDescription(),
            'campus' => $annonce->getCampus()->value,
            'type' => $annonce->getType()->value,
            'price' => $annonce->getType()->value === 'DON' ? 'Gratuit' : 'Troc',
            'category' => $annonce->getCategory()?->getName(),
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
        // Vérifier que l'utilisateur a ROLE_ADMIN ou ROLE_MODERATOR
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        $user = $this->getUser();
        
        // Récupérer l'annonce
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce non trouvée'], 404);
        }

        // Vérification de sécurité : modérateur local ne peut valider que ses annonces
        if (!$this->isGranted('ROLE_ADMIN') && $user->getModeratedCampus()) {
            if ($annonce->getCampus() !== $user->getModeratedCampus()) {
                return $this->json(['error' => 'Vous ne pouvez pas modérer cette annonce'], 403);
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

        $em->persist($annonce);
        $em->flush();

        return $this->json([
            'message' => 'Annonce ' . ($action === 'validate' ? 'validée' : 'refusée') . ' avec succès',
            'annonceId' => $id,
            'newState' => $annonce->getState()->value
        ]);
    }
}
