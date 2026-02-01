<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\AnnonceImage;
use App\Entity\User;
use App\Enum\AnnonceState;
use App\Enum\AnnonceType;
use App\Enum\Campus;
use App\Repository\AnnonceRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AnnonceApiController extends AbstractController
{
    #[Route('/api/categories', name: 'api_categories_get', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => mb_convert_encoding($category->getName(), 'UTF-8', 'UTF-8'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/annonces', name: 'api_annonces_get', methods: ['GET'])]
    public function getAnnonces(
        AnnonceRepository $annonceRepository,
        Request $request
    ): JsonResponse {
        // Créer le QueryBuilder
        $qb = $annonceRepository->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.owner', 'u')
            ->leftJoin('a.images', 'img')
            ->addSelect('c', 'u', 'img');

        // Filtre obligatoire : PUBLISHED ou COMPLETED (visible publiquement)
        // + les annonces du propriétaire connecté, quel que soit l'état
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $qb->andWhere('a.state IN (:states) OR a.owner = :currentUser')
                ->setParameter('states', ['PUBLISHED', 'COMPLETED'])
                ->setParameter('currentUser', $currentUser);
        } else {
            $qb->andWhere('a.state IN (:states)')
                ->setParameter('states', ['PUBLISHED', 'COMPLETED']);
        }

        // Filtre optionnel : Campus
        $campus = $request->query->get('campus');
        if ($campus && $campus !== 'ALL') {
            $qb->andWhere('a.campus = :campus')
                ->setParameter('campus', $campus);
        }

        // Filtre optionnel : Catégorie
        $categoryId = $request->query->get('category');
        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Filtre optionnel : Recherche texte
        $query = trim((string) $request->query->get('q', ''));
        if ($query !== '') {
            $normalizedQuery = mb_strtolower($query);
            $qb->andWhere('LOWER(a.title) LIKE :q OR LOWER(a.description) LIKE :q')
                ->setParameter('q', '%' . $normalizedQuery . '%');
        }

        // Tri par date de création (plus récentes en premier)
        $qb->orderBy('a.createdAt', 'DESC');

        // Exécuter la requête
        $annonces = $qb->getQuery()->getResult();

        $favoriteIds = [];
        if ($currentUser instanceof User) {
            foreach ($currentUser->getFavorites() as $favorite) {
                $favoriteIds[$favorite->getId()->toRfc4122()] = true;
            }
        }

        // Formater les données pour la réponse JSON
        $data = [];
        foreach ($annonces as $annonce) {
            // Récupérer la première image si elle existe
            $image = null;
            if ($annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
            }

            // Déterminer le prix/type
            $price = $annonce->getType()->value === 'DON' ? 'Gratuit' : 'Troc';

            // Extraire description (100 premiers caractères)
            $description = $annonce->getDescription();
            if (strlen($description) > 100) {
                $description = substr($description, 0, 100) . '...';
            }

            $data[] = [
                'id' => $annonce->getId()->toRfc4122(),
                'title' => mb_convert_encoding($annonce->getTitle(), 'UTF-8', 'UTF-8'),
                'description' => mb_convert_encoding($description, 'UTF-8', 'UTF-8'),
                'price' => $price,
                'campus' => $annonce->getCampus()->value,
                'category' => $annonce->getCategory() ? [
                    'id' => $annonce->getCategory()->getId(),
                    'name' => mb_convert_encoding($annonce->getCategory()->getName(), 'UTF-8', 'UTF-8')
                ] : null,
                'owner' => $annonce->getOwner()->getCasUid(),
                'image' => $image,
                'photoFilename' => $annonce->getImages()->count() > 0 ? $annonce->getImages()->first()->getImageName() : null,
                'date' => $annonce->getCreatedAt() ? $annonce->getCreatedAt()->format('d/m/Y') : null,
                'createdAt' => $annonce->getCreatedAt() ? $annonce->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'state' => $annonce->getState()->value,
                'isDonation' => $annonce->getType()->value === 'DON',
                'isFavorite' => isset($favoriteIds[$annonce->getId()->toRfc4122()]),
            ];
        }

        return new JsonResponse($data, 200, [], false);
    }

    /**
     * Récupère les détails complets d'une annonce par son ID
     * 
     * Règle de visibilité :
     * - PUBLISHED : Tout le monde peut la voir
     * - Autres états : Seul le propriétaire ou admin/modérateur
     */
    #[Route('/api/annonces/{id}', name: 'api_annonces_get_one', methods: ['GET'])]
    public function getAnnonce(
        string $id,
        AnnonceRepository $annonceRepository
    ): JsonResponse {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        // Vérifier que l'utilisateur a le droit de voir cette annonce (sauf si public)
        if (!in_array($annonce->getState()->value, ['PUBLISHED', 'COMPLETED'], true)) {
            // Si l'annonce n'est pas publiée, seul le propriétaire ou un admin peut la voir
            try {
                $this->denyAccessUnlessGranted('VIEW', $annonce);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Accès refusé.'], 403);
            }
        }

        // Déterminer si l'utilisateur connecté est propriétaire
        $isOwner = false;
        $currentUser = $this->getUser();
        if ($currentUser instanceof User) {
            $currentUserId = $currentUser->getId()->toRfc4122();
            $ownerId = $annonce->getOwner()->getId()->toRfc4122();
            $isOwner = ($currentUserId === $ownerId);
        }

        // Récupérer l'image principale
        $image = null;
        if ($annonce->getImages()->count() > 0) {
            $firstImage = $annonce->getImages()->first();
            $image = '/uploads/annonces/' . $firstImage->getImageName();
        }

        // Déterminer le prix/type d'affichage
        $displayPrice = $annonce->getType()->value === 'DON' ? 'Gratuit' : 'Troc';

        // Préparer les données du propriétaire (email visible uniquement pour le propriétaire lui-même)
        $ownerData = [
            'id' => $annonce->getOwner()->getId(),
            'username' => $annonce->getOwner()->getCasUid(),
        ];
        
        // Ajouter l'email seulement si c'est le propriétaire qui regarde
        if ($isOwner) {
            $ownerData['email'] = $annonce->getOwner()->getEmail();
        }

        $isFavorite = false;
        if ($currentUser instanceof User) {
            $isFavorite = $currentUser->getFavorites()->contains($annonce);
        }

        $data = [
            'id' => $annonce->getId()->toRfc4122(),
            'title' => $annonce->getTitle(),
            'description' => $annonce->getDescription(),
            'campus' => $annonce->getCampus()->value,
            'type' => $annonce->getType()->value,
            'price' => $displayPrice,
            'categoryId' => $annonce->getCategory()?->getId(),
            'categoryName' => $annonce->getCategory()?->getName(),
            'state' => $annonce->getState()->value,
            'image' => $image,
            'owner' => $ownerData,
            'createdAt' => $annonce->getCreatedAt()?->format('Y-m-d H:i:s'),
            'isOwner' => $isOwner,
            'isFavorite' => $isFavorite,
        ];

        return $this->json($data);
    }

    /**
     * Supprime une annonce définitivement.
     * Seul le propriétaire ou un admin peut supprimer.
     */
    #[Route('/api/annonces/{id}', name: 'api_annonces_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAnnonce(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        // Vérifier que l'utilisateur est propriétaire ou admin
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Vous devez être connecté.'], 401);
        }
        
        $isOwner = $annonce->getOwner()->getId()->toRfc4122() === $currentUser->getId()->toRfc4122();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        
        if (!$isOwner && !$isAdmin) {
            return $this->json(['error' => 'Vous n\'avez pas la permission de supprimer cette annonce.'], 403);
        }

        // Supprimer les fichiers images associés
        foreach ($annonce->getImages() as $image) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/annonces/' . $image->getImageName();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $em->remove($image);
        }

        // Supprimer l'annonce
        $em->remove($annonce);
        $em->flush();

        return $this->json(null, 204); // 204 No Content
    }

    /**
     * Marque une annonce comme terminée (donnée ou troquée).
     * Seul le propriétaire peut faire ça.
     */
    #[Route('/api/annonces/{id}/finish', name: 'api_annonces_finish', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function finishAnnonce(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        // Vérifier que l'utilisateur est propriétaire
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Vous devez être connecté.'], 401);
        }
        
        $isOwner = $annonce->getOwner()->getId()->toRfc4122() === $currentUser->getId()->toRfc4122();
        
        if (!$isOwner) {
            return $this->json(['error' => 'Seul le propriétaire peut marquer l\'annonce comme terminée.'], 403);
        }

        // Marquer comme complétée
        $annonce->setState(AnnonceState::COMPLETED);
        $em->flush();

        return $this->json([
            'message' => 'Annonce marquée comme terminée.',
            'state' => $annonce->getState()->value,
        ]);
    }

    /**
     * Édite une annonce existante.
     * RÈGLE CRITIQUE : Toute modification remet l'annonce en PENDING_REVIEW.
     */
    #[Route('/api/annonces/{id}/edit', name: 'api_annonces_edit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function editAnnonce(
        string $id,
        Request $request,
        AnnonceRepository $annonceRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        // SÉCURITÉ : Vérifier que l'utilisateur est le propriétaire
        $this->denyAccessUnlessGranted('EDIT', $annonce);

        // Récupération des données du formulaire
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $campusValue = $request->request->get('campus');
        $typeValue = $request->request->get('type');
        $categoryId = $request->request->get('categoryId');
        $imageFile = $request->files->get('image'); // Optionnel

        // Validation de base
        if (!$title || !$description || !$campusValue || !$typeValue || !$categoryId) {
            return $this->json([
                'error' => 'Tous les champs obligatoires doivent être remplis.'
            ], 400);
        }

        // Validation de l'image SI elle est fournie
        if ($imageFile) {
            $violations = $validator->validate($imageFile, [
                new Assert\File([
                    'maxSize' => '2M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP',
                    'maxSizeMessage' => 'L\'image ne doit pas dépasser 2 Mo'
                ])
            ]);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                return $this->json([
                    'error' => implode(', ', $errors)
                ], 400);
            }
        }

        // Conversion des enums
        try {
            $campus = Campus::from($campusValue);
            $type = AnnonceType::from($typeValue);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Valeurs de campus ou de type invalides.'
            ], 400);
        }

        // Récupération de la catégorie
        $category = $categoryRepository->find($categoryId);
        if (!$category) {
            return $this->json([
                'error' => 'Catégorie invalide.'
            ], 400);
        }

        // Mise à jour des propriétés texte
        $annonce->setTitle($title);
        $annonce->setDescription($description);
        $annonce->setCampus($campus);
        $annonce->setType($type);
        $annonce->setCategory($category);

        // Traitement de l'image SI une nouvelle image est uploadée
        if ($imageFile) {
            // Suppression de l'ancienne image
            if ($annonce->getImages()->count() > 0) {
                $oldImage = $annonce->getImages()->first();
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/annonces/' . $oldImage->getImageName();
                
                // Supprimer le fichier physique si il existe
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }

                // Supprimer l'entité AnnonceImage
                $em->remove($oldImage);
            }

            // Upload de la nouvelle image
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/annonces',
                    $newFilename
                );
            } catch (FileException $e) {
                return $this->json([
                    'error' => 'Erreur lors de l\'upload de l\'image.'
                ], 500);
            }

            // Création de la nouvelle image associée
            $annonceImage = new AnnonceImage();
            $annonceImage->setImageName($newFilename);
            $annonceImage->setAnnonce($annonce);
            $annonce->getImages()->add($annonceImage);
            
            $em->persist($annonceImage);
        }

        // ⚠️ RÈGLE D'OR : Toute modification remet l'annonce en attente de validation
        $annonce->setState(AnnonceState::PENDING_REVIEW);
        $annonce->setRefusalReason(null); // Vider le motif de refus précédent

        $em->flush();

        return $this->json([
            'message' => 'Annonce modifiée avec succès et soumise à validation.',
            'annonceId' => $annonce->getId()->toRfc4122()
        ]);
    }
}
