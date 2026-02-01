<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Enum\Campus;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminUserApiController extends AbstractController
{
    #[Route('/api/admin/users', name: 'api_admin_users', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $data = array_map(static function (User $user): array {
            return [
                'id' => $user->getId()?->toRfc4122(),
                'cas_uid' => $user->getCasUid(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'moderated_campus' => $user->getModeratedCampus()?->value,
                'is_banned' => $user->isBanned(),
                'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $users);

        return $this->json($data);
    }

    #[Route('/api/admin/users/{id}/promote', name: 'api_admin_users_promote', methods: ['POST'])]
    public function promoteUser(
        string $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $role = $payload['role'] ?? null;
        $campusValue = $payload['campus'] ?? null;

        if (!in_array($role, ['USER', 'MODERATOR'], true)) {
            return $this->json(['error' => 'Rôle invalide.'], 400);
        }

        $roles = $user->getRoles();

        if ($role === 'USER') {
            $roles = array_values(array_diff($roles, ['ROLE_MODERATOR']));
            $user->setModeratedCampus(null);
        }

        if ($role === 'MODERATOR') {
            if (!$campusValue) {
                return $this->json(['error' => 'Campus requis pour un modérateur.'], 400);
            }

            try {
                $campus = Campus::from($campusValue);
            } catch (\ValueError $e) {
                return $this->json(['error' => 'Campus invalide.'], 400);
            }

            if (!in_array('ROLE_MODERATOR', $roles, true)) {
                $roles[] = 'ROLE_MODERATOR';
            }

            $user->setModeratedCampus($campus);
        }

        // Ne jamais toucher au ROLE_ADMIN via cette API
        $roles = array_values(array_unique($roles));
        $user->setRoles($roles);

        $em->flush();

        return $this->json([
            'id' => $user->getId()?->toRfc4122(),
            'cas_uid' => $user->getCasUid(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'moderated_campus' => $user->getModeratedCampus()?->value,
            'is_banned' => $user->isBanned(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/admin/users/{id}/ban', name: 'api_admin_users_ban', methods: ['POST'])]
    public function banUser(
        string $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId()?->toRfc4122() === $id) {
            return $this->json(['error' => 'Vous ne pouvez pas vous bannir vous-même.'], 400);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        $willBeBanned = !$user->isBanned();
        $payload = json_decode($request->getContent(), true) ?? [];
        $reason = isset($payload['reason']) ? trim((string) $payload['reason']) : '';

        if ($willBeBanned && $reason === '') {
            return $this->json(['error' => 'Motif obligatoire.'], 400);
        }

        $user->setIsBanned($willBeBanned);
        $em->flush();

        $recipientName = $user->getCasUid() ?? 'Utilisateur';

        if ($user->isBanned()) {
            $email = (new Email())
                ->from('no-reply@ulcoccaz.fr')
                ->to($user->getEmail())
                ->subject("⚠️ Suspension de votre compte ULC'OCCAZ")
                ->html(
                    sprintf(
                        'Bonjour %s,<br><br>
                        Votre compte a été suspendu pour la raison suivante :<br>
                        <strong>%s</strong><br><br>
                        Cordialement,',
                        htmlspecialchars($recipientName, ENT_QUOTES),
                        htmlspecialchars($reason, ENT_QUOTES)
                    )
                );

            $mailer->send($email);
        } else {
            $email = (new Email())
                ->from('no-reply@ulcoccaz.fr')
                ->to($user->getEmail())
                ->subject('✅ Bonne nouvelle : Votre compte est réactivé')
                ->html(
                    sprintf(
                        'Bonjour %s,<br><br>
                        Votre compte a été réactivé. Vous pouvez à nouveau vous connecter.<br><br>
                        Cordialement,',
                        htmlspecialchars($recipientName, ENT_QUOTES)
                    )
                );

            $mailer->send($email);
        }

        return $this->json([
            'id' => $user->getId()?->toRfc4122(),
            'cas_uid' => $user->getCasUid(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'moderated_campus' => $user->getModeratedCampus()?->value,
            'is_banned' => $user->isBanned(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
