<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/choisir-alias', name: 'app_user_alias_setup', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function aliasSetup(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('set_alias', $token)) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => 'Token CSRF invalide. Veuillez réessayer.',
                    'mode' => 'setup',
                ]);
            }

            $alias = mb_strtolower(trim((string) $request->request->get('alias', '')));

            $error = $this->validateAlias($alias);
            if ($error !== null) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => $error,
                    'alias' => $alias,
                    'mode' => 'setup',
                ]);
            }

            if ($this->aliasExists($userRepository, $alias, $user)) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => 'Cet alias est déjà utilisé. Veuillez en choisir un autre.',
                    'alias' => $alias,
                    'mode' => 'setup',
                ]);
            }

            $user->setAlias($alias);
            $em->flush();

            $this->addFlash('success', 'Votre alias a été enregistré avec succès.');

            if ($user->getCharteAgreements()->isEmpty()) {
                return $this->redirectToRoute('app_charte_stepper');
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/alias_setup.html.twig', [
            'alias' => $user->getAlias(),
            'mode' => 'setup',
        ]);
    }

    #[Route('/mon-alias', name: 'app_user_alias_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function aliasEdit(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('set_alias', $token)) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => 'Token CSRF invalide. Veuillez réessayer.',
                    'alias' => $user->getAlias(),
                    'mode' => 'edit',
                ]);
            }

            $alias = mb_strtolower(trim((string) $request->request->get('alias', '')));

            $error = $this->validateAlias($alias);
            if ($error !== null) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => $error,
                    'alias' => $alias,
                    'mode' => 'edit',
                ]);
            }

            if ($this->aliasExists($userRepository, $alias, $user)) {
                return $this->render('user/alias_setup.html.twig', [
                    'error' => 'Cet alias est déjà utilisé. Veuillez en choisir un autre.',
                    'alias' => $alias,
                    'mode' => 'edit',
                ]);
            }

            $user->setAlias($alias);
            $em->flush();

            $this->addFlash('success', 'Votre alias a été modifié avec succès.');

            return $this->redirectToRoute('app_user_alias_edit');
        }

        return $this->render('user/alias_setup.html.twig', [
            'alias' => $user->getAlias(),
            'mode' => 'edit',
        ]);
    }

    private function validateAlias(string $alias): ?string
    {
        if ($alias === '') {
            return 'Veuillez saisir un alias.';
        }

        if (mb_strlen($alias) < 3 || mb_strlen($alias) > 20) {
            return 'L\'alias doit contenir entre 3 et 20 caractères.';
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $alias)) {
            return 'L\'alias ne peut contenir que des lettres minuscules, chiffres, points, tirets et underscores.';
        }

        return null;
    }

    private function aliasExists(UserRepository $userRepository, string $alias, User $currentUser): bool
    {
        $exists = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('LOWER(u.alias) = :alias')
            ->andWhere('u.id != :userId')
            ->setParameter('alias', $alias)
            ->setParameter('userId', $currentUser->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $exists > 0;
    }

    #[Route('/mes-annonces', name: 'app_user_annonces')]
    #[IsGranted('ROLE_USER')]
    public function myAnnonces(): Response
    {
        return $this->render('user/my_annonces.html.twig');
    }

    #[Route('/messages', name: 'app_user_messages')]
    #[Route('/mes-messages', name: 'app_user_messages_short')]
    #[Route('/mes-messages/{conversationId}', name: 'app_user_messages_conversation', defaults: ['conversationId' => null])]
    #[IsGranted('ROLE_USER')]
    public function messages(?string $conversationId = null): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $currentUserId = $user?->getId()?->toRfc4122();
        $mercureUrl = $_ENV['MERCURE_PUBLIC_URL'] ?? '';
        return $this->render('user/messages.html.twig', [
            'conversationId' => $conversationId,
            'currentUserId' => $currentUserId,
            'mercureUrl' => $mercureUrl,
        ]);
    }

    #[Route('/mes-favoris', name: 'app_user_favorites')]
    #[IsGranted('ROLE_USER')]
    public function favorites(): Response
    {
        return $this->render('user/favorites.html.twig');
    }
}
