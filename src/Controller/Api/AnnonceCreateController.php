<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\AnnonceImage;
use App\Enum\AnnonceState;
use App\Enum\AnnonceType;
use App\Enum\Campus;
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

#[IsGranted('ROLE_USER')]
class AnnonceCreateController extends AbstractController
{
    #[Route('/api/annonces/new', name: 'api_annonces_create', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        // Récupération des données du formulaire
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $campusValue = $request->request->get('campus');
        $typeValue = $request->request->get('type');
        $categoryId = $request->request->get('categoryId');
        $imageFile = $request->files->get('image');

        // Validation de base
        if (!$title || !$description || !$campusValue || !$typeValue || !$categoryId) {
            return $this->json([
                'error' => 'Tous les champs obligatoires doivent être remplis.'
            ], 400);
        }

        // Validation de l'image
        if (!$imageFile) {
            return $this->json([
                'error' => 'Une image est obligatoire.'
            ], 400);
        }

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

        // Traitement de l'image
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        // Nettoyer le nom de fichier (retirer les caractères spéciaux)
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

        // Création de l'annonce
        $annonce = new Annonce();
        $annonce->setTitle($title);
        $annonce->setDescription($description);
        $annonce->setCampus($campus);
        $annonce->setType($type);
        $annonce->setCategory($category);
        
        // Champs automatiques (Règles Métier)
        $annonce->setState(AnnonceState::PENDING_REVIEW); // En attente de modération
        $annonce->setOwner($this->getUser());
        $annonce->setExpiresAt(new \DateTime('+6 months')); // 6 mois de validité

        // Création de l'image associée
        $annonceImage = new AnnonceImage();
        $annonceImage->setImageName($newFilename);
        $annonceImage->setAnnonce($annonce);

        // Ajout de l'image à l'annonce
        $annonce->getImages()->add($annonceImage);

        // Persistance
        $em->persist($annonce);
        $em->persist($annonceImage);
        $em->flush();

        return $this->json([
            'message' => 'Annonce créée avec succès et envoyée pour validation !',
            'annonceId' => $annonce->getId()->toRfc4122()
        ], 201);
    }
}
