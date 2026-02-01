<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AnnonceController extends AbstractController
{
    #[Route('/annonce/new', name: 'app_annonce_new')]
    public function new(): Response
    {
        return $this->render('annonce/new.html.twig');
    }

    /**
     * Page d'édition d'une annonce.
     * Vérifie que l'utilisateur est propriétaire via le Voter EDIT.
     */
    #[Route('/annonce/{id}/edit', name: 'app_annonce_edit')]
    public function edit(string $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            throw $this->createNotFoundException('Annonce introuvable.');
        }

        // Vérification de sécurité via le Voter
        $this->denyAccessUnlessGranted('EDIT', $annonce);

        return $this->render('annonce/edit.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    /**
     * Page de détail d'une annonce.
     * Accessible à tous pour les annonces PUBLISHED.
     * Restreint aux propriétaires/admins pour les autres états.
     */
    #[Route('/annonce/{id}', name: 'app_annonce_show')]
    public function show(string $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            throw $this->createNotFoundException('Annonce introuvable.');
        }

        // Vérification de sécurité : publiée = tout le monde, sinon propriétaire/admin
        if ($annonce->getState()->value !== 'PUBLISHED') {
            try {
                $this->denyAccessUnlessGranted('VIEW', $annonce);
            } catch (\Exception $e) {
                throw $this->createAccessDeniedException('Vous n\'avez pas la permission de voir cette annonce.');
            }
        }

        return $this->render('annonce/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }
}
