<?php

namespace App\Controller;

use App\Entity\CharteAgreement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CharteController extends AbstractController
{
    #[Route('/charte', name: 'app_charte')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('charte/index.html.twig');
    }

    #[Route('/charte/sign', name: 'app_charte_sign', methods: ['POST'])]
    public function sign(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $agreement = new CharteAgreement();
        $agreement
            ->setUser($user)
            ->setSectionName('general_v1')
            ->setAgreedAt(new \DateTimeImmutable());

        $entityManager->persist($agreement);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }
}
