<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FavoriteApiController extends AbstractController
{
    #[Route('/api/annonces/{id}/favorite', name: 'api_annonce_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(
        string $id,
        AnnonceRepository $annonceRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            return $this->json(['error' => 'Annonce introuvable.'], 404);
        }

        if ($user->getFavorites()->contains($annonce)) {
            $user->removeFavorite($annonce);
            $isFavorite = false;
        } else {
            $user->addFavorite($annonce);
            $isFavorite = true;
        }

        $em->persist($user);
        $em->flush();

        return $this->json(['isFavorite' => $isFavorite]);
    }

    #[Route('/api/favorites', name: 'api_favorites_get', methods: ['GET'])]
    public function getFavorites(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $favorites = $user->getFavorites();
        $data = [];

        foreach ($favorites as $annonce) {
            $image = null;
            if ($annonce->getImages()->count() > 0) {
                $firstImage = $annonce->getImages()->first();
                $image = '/uploads/annonces/' . $firstImage->getImageName();
            }

            $price = $annonce->getType()->value === 'DON' ? 'Gratuit' : 'Troc';

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
                'isFavorite' => true,
            ];
        }

        return $this->json($data);
    }
}
