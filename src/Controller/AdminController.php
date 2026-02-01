<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Vérifier que l'utilisateur a ROLE_ADMIN ou ROLE_MODERATOR
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('admin/users.html.twig');
    }

    #[Route('/admin/moderation/annonce/{id}', name: 'app_admin_moderation_show')]
    public function moderationShow(string $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('admin/moderation_show.html.twig', [
            'id' => $id,
        ]);
    }
}
