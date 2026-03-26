<?php

namespace App\Controller\Api;

use App\Entity\CharteAgreement;
use App\Repository\CharteAgreementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/user/charte', name: 'api_user_charte_')]
class CharteController extends AbstractController
{
    #[Route('/accept', name: 'accept', methods: ['POST'])]
    public function acceptCharte(
        Request $request,
        EntityManagerInterface $em,
        CharteAgreementRepository $charteRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);
        $sections = $data['sections'] ?? [];

        if (empty($sections)) {
            return $this->json([
                'message' => 'Aucune section fournie'
            ], 400);
        }

        try {
            // Créer un enregistrement pour chaque section acceptée
            foreach ($sections as $sectionName) {
                // Vérifier si l'utilisateur a déjà accepté cette section
                $existing = $charteRepo->findOneBy([
                    'user' => $user,
                    'sectionName' => $sectionName
                ]);

                if (!$existing) {
                    $agreement = new CharteAgreement();
                    $agreement->setUser($user);
                    $agreement->setSectionName($sectionName);
                    $agreement->setAgreedAt(new \DateTimeImmutable());

                    $em->persist($agreement);
                }
            }

            $em->flush();

            return $this->json([
                'message' => 'Charte acceptée avec succès',
                'sections_accepted' => count($sections),
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'enregistrement de la charte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optionnel : Endpoint pour vérifier si l'utilisateur a accepté la charte
     */
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function charteStatus(CharteAgreementRepository $charteRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'accepted' => false
            ], 401);
        }

        $agreements = $charteRepo->findBy(['user' => $user]);

        return $this->json([
            'accepted' => count($agreements) > 0,
            'sections_accepted' => count($agreements),
            'agreements' => array_map(fn($agreement) => [
                'section' => $agreement->getSectionName(),
                'accepted_at' => $agreement->getAgreedAt()->format('Y-m-d H:i:s')
            ], $agreements)
        ], 200);
    }
}
