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
        $campusesJson = $request->request->get('campuses');
        $typeValue = $request->request->get('type');
        $categoryId = $request->request->get('categoryId');
        $customCategory = $request->request->get('customCategory');
        
        // Décoder les campus (tableau JSON)
        $campusesArray = [];
        if ($campusesJson) {
            $decoded = json_decode($campusesJson, true);
            if (is_array($decoded)) {
                $campusesArray = $decoded;
            }
        }
        
        // Récupérer TOUTES les images (support multi-upload)
        $imageFiles = $request->files->get('images', []);
        
        // Si une seule image est envoyée avec le nom 'image'
        if (!$imageFiles || empty($imageFiles)) {
            $singleImage = $request->files->get('image');
            if ($singleImage) {
                $imageFiles = [$singleImage];
            }
        }

        // Validation de base avec messages détaillés
        $missingFields = [];
        if (!$title) $missingFields[] = 'title';
        if (!$description) $missingFields[] = 'description';
        if (empty($campusesArray)) $missingFields[] = 'campuses (au moins un campus requis)';
        if (!$typeValue) $missingFields[] = 'type';
        if (!$categoryId) $missingFields[] = 'categoryId';
        
        // Vérifier que si catégorie = 'other', customCategory est fourni
        if ($categoryId === 'other' && !$customCategory) {
            $missingFields[] = 'customCategory (obligatoire si catégorie=Autre)';
        }
        
        if (!empty($missingFields)) {
            return $this->json([
                'error' => 'Champs manquants : ' . implode(', ', $missingFields),
                'received' => [
                    'title' => $title,
                    'description' => $description ? substr($description, 0, 50) . '...' : null,
                    'campuses' => $campusesArray,
                    'type' => $typeValue,
                    'categoryId' => $categoryId,
                ]
            ], 400);
        }
        
        // Validation de la description (2000 caractères max)
        if (strlen($description) > 2000) {
            return $this->json([
                'error' => 'La description ne peut pas dépasser 2000 caractères.'
            ], 400);
        }

        // Validation des images
        if (empty($imageFiles)) {
            return $this->json([
                'error' => 'Au moins une image est obligatoire.'
            ], 400);
        }
        
        // Vérifier le nombre d'images (max 6)
        if (count($imageFiles) > 6) {
            return $this->json([
                'error' => 'Vous ne pouvez pas uploader plus de 6 images.'
            ], 400);
        }

        // Valider chaque image
        foreach ($imageFiles as $index => $imageFile) {
            $violations = $validator->validate($imageFile, [
                new Assert\File([
                    'maxSize' => '1M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP',
                    'maxSizeMessage' => 'Chaque image ne doit pas dépasser 1 Mo'
                ])
            ]);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                return $this->json([
                    'error' => "Image " . ($index + 1) . " : " . implode(', ', $errors)
                ], 400);
            }
        }
        
        // Validation des campus (vérifier que chaque valeur est un campus valide)
        $validatedCampuses = [];
        foreach ($campusesArray as $campusValue) {
            try {
                // Vérifier que la valeur est un campus valide
                Campus::from($campusValue);
                $validatedCampuses[] = $campusValue;
            } catch (\ValueError $e) {
                return $this->json([
                    'error' => "Campus invalide : {$campusValue}"
                ], 400);
            }
        }
        
        // Conversion du type
        try {
            $type = AnnonceType::from($typeValue);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Valeur de type invalide.'
            ], 400);
        }

        // Récupération de la catégorie
        // Si "other" est envoyé, ne pas vérifier la catégorie en BD
        $category = null;
        if ($categoryId !== 'other') {
            $category = $categoryRepository->find($categoryId);
            if (!$category) {
                return $this->json([
                    'error' => 'Catégorie invalide.'
                ], 400);
            }
        } else {
            // Créer une catégorie par défaut pour "Autre" ou utiliser la première catégorie
            // On peut aussi créer une catégorie "Autre" spécifique
            // Pour l'instant, on va utiliser la première catégorie et stocker le custom name
            $category = $categoryRepository->findOneBy([], ['id' => 'ASC']);
            if (!$category) {
                return $this->json([
                    'error' => 'Impossible de traiter une catégorie personnalisée.'
                ], 400);
            }
        }

        // Création de l'annonce
        $annonce = new Annonce();
        $annonce->setTitle($title);
        $annonce->setDescription($description);
        $annonce->setCampuses($validatedCampuses);
        $annonce->setType($type);
        $annonce->setCategory($category);
        
        // Stocker la catégorie personnalisée si fournie
        if ($customCategory) {
            $annonce->setCustomCategoryName(trim($customCategory));
        }
        
        // Champs automatiques (Règles Métier)
        $annonce->setState(AnnonceState::PENDING_REVIEW); // En attente de modération
        $annonce->setOwner($this->getUser());
        $annonce->setExpiresAt(new \DateTime('+6 months')); // 6 mois de validité

        // Traitement de toutes les images
        foreach ($imageFiles as $imageFile) {
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
                    'error' => 'Erreur lors de l\'upload des images.'
                ], 500);
            }

            // Création de l'image associée
            $annonceImage = new AnnonceImage();
            $annonceImage->setImageName($newFilename);
            $annonceImage->setAnnonce($annonce);

            // Ajout de l'image à l'annonce
            $annonce->getImages()->add($annonceImage);
            $em->persist($annonceImage);
        }

        // Persistance
        $em->persist($annonce);
        $em->flush();

        return $this->json([
            'message' => 'Annonce créée avec succès et envoyée pour validation !',
            'annonceId' => $annonce->getId()->toRfc4122()
        ], 201);
    }
}
