<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:bootstrap-default-data',
    description: 'Crée des comptes par défaut et les catégories essentielles en production'
)]
class BootstrapDefaultDataCommand extends Command
{
    private const DEFAULT_PASSWORD = '00000000';

    /**
     * @var list<array{casUid: string, email: string, alias: string, roles: list<string>}>
     */
    private const DEFAULT_USERS = [
        [
            'casUid' => 'shahram.bahrami@eilco.univ-littoral.fr',
            'email' => 'shahram.bahrami@eilco.univ-littoral.fr',
            'alias' => 'shahram.bahrami',
            'roles' => ['ROLE_RESPONSABLE', 'ROLE_MODERATOR', 'ROLE_USER'],
        ],
        [
            'casUid' => 'denis.robillard@univ-littoral.fr',
            'email' => 'denis.robillard@univ-littoral.fr',
            'alias' => 'denis.robillard',
            'roles' => ['ROLE_MODERATOR', 'ROLE_USER'],
        ],
        [
            'casUid' => 'saif-eddine.elkhantache@etu.eilco.univ-littoral.fr',
            'email' => 'saif-eddine.elkhantache@etu.eilco.univ-littoral.fr',
            'alias' => 'saif.elkhantache',
            'roles' => ['ROLE_USER'],
        ],
    ];

    /**
     * @var list<string>
     */
    private const DEFAULT_CATEGORIES = [
        'Livres',
        'Matériel Informatique',
        'Mobilier',
        'Vêtements',
        'Électroménager',
        'Vaisselle',
        'Fournitures Scolaires',
        'Sport',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isBootstrapEnabled()) {
            $io->text('Bootstrap ignoré : APP_BOOTSTRAP_USERS n\'est pas activé.');

            return Command::SUCCESS;
        }

        $password = $this->getBootstrapPassword();
        $createdUsers = 0;
        $createdCategories = 0;

        foreach (self::DEFAULT_USERS as $userData) {
            $existingUser = $this->userRepository->findOneBy(['casUid' => $userData['casUid']]);
            if ($existingUser instanceof User) {
                continue;
            }

            $user = new User();
            $user
                ->setCasUid($userData['casUid'])
                ->setEmail($userData['email'])
                ->setAlias($userData['alias'])
                ->setRoles($userData['roles'])
                ->setPassword($this->passwordHasher->hashPassword($user, $password));

            $this->em->persist($user);
            ++$createdUsers;
        }

        foreach (self::DEFAULT_CATEGORIES as $categoryName) {
            $slug = $this->slugify($categoryName);
            $existingCategory = $this->categoryRepository->findOneBy(['slug' => $slug]);
            if ($existingCategory instanceof Category) {
                continue;
            }

            $category = new Category();
            $category
                ->setName($categoryName)
                ->setSlug($slug);

            $this->em->persist($category);
            ++$createdCategories;
        }

        if ($createdUsers === 0 && $createdCategories === 0) {
            $io->success('Aucune donnée par défaut à créer : tout existe déjà.');

            return Command::SUCCESS;
        }

        $this->em->flush();

        $io->success(sprintf(
            'Bootstrap terminé : %d utilisateur(s) et %d catégorie(s) créés.',
            $createdUsers,
            $createdCategories,
        ));

        if ($createdUsers > 0) {
            $io->note(sprintf(
                'Comptes créés : shahram.bahrami@eilco.univ-littoral.fr / denis.robillard@univ-littoral.fr / saif-eddine.elkhantache@etu.eilco.univ-littoral.fr. Mot de passe actuel : %s',
                $password,
            ));
        }

        return Command::SUCCESS;
    }

    private function isBootstrapEnabled(): bool
    {
        $value = strtolower(trim((string) ($_SERVER['APP_BOOTSTRAP_USERS'] ?? $_ENV['APP_BOOTSTRAP_USERS'] ?? '0')));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function getBootstrapPassword(): string
    {
        $password = trim((string) ($_SERVER['APP_BOOTSTRAP_PASSWORD'] ?? $_ENV['APP_BOOTSTRAP_PASSWORD'] ?? ''));

        return $password !== '' ? $password : self::DEFAULT_PASSWORD;
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower($value);
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?: $normalized;

        return trim($normalized, '-');
    }
}