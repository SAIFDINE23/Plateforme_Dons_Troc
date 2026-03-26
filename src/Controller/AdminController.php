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
        // Dashboard accessible aux MODERATOR et RESPONSABLE
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(): Response
    {
        // MODERATOR et RESPONSABLE peuvent voir la liste des utilisateurs
        // Seuls les RESPONSABLE peuvent modifier les rôles
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('admin/users.html.twig');
    }

    #[Route('/admin/moderation/annonce/{id}', name: 'app_admin_moderation_show')]
    public function moderationShow(string $id): Response
    {
        // Modération d'annonces accessible aux MODERATOR et RESPONSABLE
        if (!$this->isGranted('ROLE_MODERATOR')) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('admin/moderation_show.html.twig', [
            'id' => $id,
        ]);
    }
}
