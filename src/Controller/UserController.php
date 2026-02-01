<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/mes-annonces', name: 'app_user_annonces')]
    #[IsGranted('ROLE_USER')]
    public function myAnnonces(): Response
    {
        return $this->render('user/my_annonces.html.twig');
    }

    #[Route('/messages', name: 'app_user_messages')]
    #[IsGranted('ROLE_USER')]
    public function messages(): Response
    {
        return $this->render('user/messages.html.twig');
    }
}
