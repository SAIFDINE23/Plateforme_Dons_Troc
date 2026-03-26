<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            /** @var User $user */
            $user = $this->getUser();

            // Vérifier si l'utilisateur a défini un alias
            if ($user->getAlias() === null || trim($user->getAlias()) === '') {
                return $this->redirectToRoute('app_user_alias_setup');
            }
            
            // Vérifier si l'utilisateur a accepté la charte
            if ($user->getCharteAgreements()->isEmpty()) {
                // Pas accepté la charte → rediriger vers le stepper
                return $this->redirectToRoute('app_charte_stepper');
            }
            
            // Charte acceptée → rediriger vers l'accueil
            return $this->redirectToRoute('app_home');
        }

        // Récupère l'erreur de login si elle existe
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier CAS UID saisi
        $lastCasUid = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_cas_uid' => $lastCasUid,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
