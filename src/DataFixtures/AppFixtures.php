<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_PASSWORD = '00000000';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            // 2 Responsables (peuvent tout faire: modération + gestion des modérateurs)
            [
                'firstName' => 'Alice',
                'lastName' => 'Responsable',
                'roles' => ['ROLE_RESPONSABLE', 'ROLE_MODERATOR', 'ROLE_USER'],
                'isStaff' => true,
            ],
            [
                'firstName' => 'Bob',
                'lastName' => 'Responsable',
                'roles' => ['ROLE_RESPONSABLE', 'ROLE_MODERATOR', 'ROLE_USER'],
                'isStaff' => true,
            ],

            // 2 Modérateurs (gèrent les annonces + bannir users de tous les campus)
            [
                'firstName' => 'Jean',
                'lastName' => 'Moderator',
                'roles' => ['ROLE_MODERATOR', 'ROLE_USER'],
                'isStaff' => true,
            ],
            [
                'firstName' => 'Marie',
                'lastName' => 'Moderator',
                'roles' => ['ROLE_MODERATOR', 'ROLE_USER'],
                'isStaff' => true,
            ],

            // 4 Étudiants normaux
            [
                'firstName' => 'Sophie',
                'lastName' => 'Leroy',
                'roles' => ['ROLE_USER'],
                'isStaff' => false,
            ],
            [
                'firstName' => 'Emma',
                'lastName' => 'Petit',
                'roles' => ['ROLE_USER'],
                'isStaff' => false,
            ],
            [
                'firstName' => 'Hugo',
                'lastName' => 'Moreau',
                'roles' => ['ROLE_USER'],
                'isStaff' => false,
            ],
            [
                'firstName' => 'Lucas',
                'lastName' => 'Dubois',
                'roles' => ['ROLE_USER'],
                'isStaff' => false,
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();

            $casUid = $this->buildCasUid($userData['firstName'], $userData['lastName']);
            $email = $this->buildEmail($userData['firstName'], $userData['lastName'], $userData['isStaff']);

            $user
                ->setCasUid($casUid)
                ->setEmail($email)
                ->setRoles($userData['roles']);

            $this->hashPassword($user);

            $manager->persist($user);
        }

        // Créer les catégories (nécessaires pour le fonctionnement de l'application)
        $this->createCategories($manager);

        $manager->flush();
    }

    private function createCategories(ObjectManager $manager): void
    {
        $categoryNames = [
            'Livres',
            'Matériel Informatique',
            'Mobilier',
            'Vêtements',
            'Électroménager',
            'Vaisselle',
            'Fournitures Scolaires',
            'Sport',
        ];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower(str_replace(' ', '-', $this->normalizeName($name))));

            $manager->persist($category);
        }
    }

    private function buildCasUid(string $firstName, string $lastName): string
    {
        $first = $this->normalizeName($firstName);
        $last = $this->normalizeName($lastName);

        return substr($first, 0, 1) . $last;
    }

    private function buildEmail(string $firstName, string $lastName, bool $isStaff): string
    {
        $first = $this->normalizeName($firstName);
        $last = $this->normalizeName($lastName);

        $domain = $isStaff ? 'univ-littoral.fr' : 'etu.univ-littoral.fr';

        return sprintf('%s.%s@%s', $first, $last, $domain);
    }

    private function normalizeName(string $name): string
    {
        $normalized = strtolower($name);
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        $normalized = preg_replace('/[^a-z]/', '', $normalized) ?: $normalized;

        return $normalized;
    }

    private function hashPassword(User $user): void
    {
        $proxy = new class($user) implements PasswordAuthenticatedUserInterface {
            public function __construct(private User $user) {}

            public function getPassword(): ?string
            {
                return $this->user->getPassword();
            }
        };

        $hashed = $this->passwordHasher->hashPassword($proxy, self::DEFAULT_PASSWORD);
        $user->setPassword($hashed);
    }
}
