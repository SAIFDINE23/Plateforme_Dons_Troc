<?php

namespace App\DataFixtures;

use App\Entity\Annonce;
use App\Entity\Category;
use App\Entity\User;
use App\Enum\AnnonceState;
use App\Enum\AnnonceType;
use App\Enum\Campus;
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
            // 1 Admin Global (Super Admin)
            [
                'firstName' => 'Admin',
                'lastName' => 'Global',
                'roles' => ['ROLE_ADMIN'],
                'campus' => null,
                'isStaff' => true,
            ],

            // 4 Modérateurs de Campus
            [
                'firstName' => 'Jean',
                'lastName' => 'Dupont',
                'roles' => ['ROLE_MODERATOR'],
                'campus' => Campus::CALAIS,
                'isStaff' => true,
            ],
            [
                'firstName' => 'Marie',
                'lastName' => 'Curie',
                'roles' => ['ROLE_MODERATOR'],
                'campus' => Campus::DUNKERQUE,
                'isStaff' => true,
            ],
            [
                'firstName' => 'Paul',
                'lastName' => 'Martin',
                'roles' => ['ROLE_MODERATOR'],
                'campus' => Campus::BOULOGNE,
                'isStaff' => true,
            ],
            [
                'firstName' => 'Luc',
                'lastName' => 'Bernard',
                'roles' => ['ROLE_MODERATOR'],
                'campus' => Campus::SAINT_OMER,
                'isStaff' => true,
            ],

            // 3 Étudiants
            [
                'firstName' => 'Sophie',
                'lastName' => 'Leroy',
                'roles' => ['ROLE_USER'],
                'campus' => null,
                'isStaff' => false,
            ],
            [
                'firstName' => 'Emma',
                'lastName' => 'Petit',
                'roles' => ['ROLE_USER'],
                'campus' => null,
                'isStaff' => false,
            ],
            [
                'firstName' => 'Hugo',
                'lastName' => 'Moreau',
                'roles' => ['ROLE_USER'],
                'campus' => null,
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
                ->setRoles($userData['roles'])
                ->setModeratedCampus($userData['campus']);

            $this->hashPassword($user);

            $manager->persist($user);
        }

        $manager->flush();

        // Créer des catégories
        $categories = $this->createCategories($manager);

        // Créer des annonces de test
        $this->createAnnonces($manager, $categories);

        $manager->flush();
    }

    private function createCategories(ObjectManager $manager): array
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

        $categories = [];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower(str_replace(' ', '-', $this->normalizeName($name))));

            $manager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    private function createAnnonces(ObjectManager $manager, array $categories): void
    {
        // Récupérer quelques utilisateurs
        $users = $manager->getRepository(User::class)->findAll();
        if (empty($users)) {
            return;
        }

        $annoncesData = [
            [
                'title' => 'Livre de mathématiques L1',
                'description' => 'Livre de mathématiques niveau L1 en excellent état. Parfait pour réviser les bases de l\'analyse et de l\'algèbre linéaire. Contient des exercices corrigés et des rappels de cours.',
                'type' => AnnonceType::DON,
                'campus' => Campus::CALAIS,
                'category' => 0, // Livres
            ],
            [
                'title' => 'Clavier mécanique gaming',
                'description' => 'Clavier mécanique avec rétroéclairage RGB. Fonctionne parfaitement, switches Cherry MX Red. Idéal pour gaming ou programmation. Disponible pour troc contre un écran.',
                'type' => AnnonceType::TROC,
                'campus' => Campus::DUNKERQUE,
                'category' => 1, // Matériel Informatique
            ],
            [
                'title' => 'Bureau étudiant IKEA',
                'description' => 'Bureau blanc IKEA modèle MICKE, dimensions 105x50cm. Quelques traces d\'usage mais très solide. Parfait pour une chambre étudiante.',
                'type' => AnnonceType::DON,
                'campus' => Campus::BOULOGNE,
                'category' => 2, // Mobilier
            ],
            [
                'title' => 'Pull universitaire ULCO taille M',
                'description' => 'Pull officiel ULCO taille M, couleur bleu marine. Porté seulement quelques fois. Parfait état, très chaud pour l\'hiver.',
                'type' => AnnonceType::DON,
                'campus' => Campus::SAINT_OMER,
                'category' => 3, // Vêtements
            ],
            [
                'title' => 'Micro-ondes compact',
                'description' => 'Micro-ondes compact 20L, puissance 700W. Fonctionne parfaitement, facile à transporter. À donner car je déménage.',
                'type' => AnnonceType::DON,
                'campus' => Campus::CALAIS,
                'category' => 4, // Électroménager
            ],
            [
                'title' => 'Cours de physique L2 complets',
                'description' => 'Polycopiés de tous les cours de physique L2 (mécanique, thermodynamique, électromagnétisme). Notes de cours + TD corrigés. État impeccable.',
                'type' => AnnonceType::TROC,
                'campus' => Campus::DUNKERQUE,
                'category' => 6, // Fournitures Scolaires
            ],
            [
                'title' => 'Raquette de badminton',
                'description' => 'Raquette de badminton Yonex en bon état, cordée récemment. Livrée avec housse de protection. Parfaite pour jouer au gymnase universitaire.',
                'type' => AnnonceType::DON,
                'campus' => Campus::BOULOGNE,
                'category' => 7, // Sport
            ],
            [
                'title' => 'Calculatrice scientifique TI-83',
                'description' => 'Calculatrice scientifique Texas Instruments TI-83 en parfait état de fonctionnement. Piles neuves incluses. Idéale pour les cours de maths et physique.',
                'type' => AnnonceType::TROC,
                'campus' => Campus::CALAIS,
                'category' => 6, // Fournitures Scolaires
            ],
            [
                'title' => 'Cafetière électrique',
                'description' => 'Cafetière électrique filtre 1,2L. Plaque chauffante pour maintenir au chaud. Très peu utilisée, comme neuve. Indispensable pour les longues nuits de révision!',
                'type' => AnnonceType::DON,
                'campus' => Campus::SAINT_OMER,
                'category' => 4, // Électroménager
            ],
            [
                'title' => 'Lampe de bureau LED',
                'description' => 'Lampe de bureau LED orientable avec 3 niveaux d\'intensité. Bras articulé et base stable. Parfaite pour étudier le soir.',
                'type' => AnnonceType::DON,
                'campus' => Campus::DUNKERQUE,
                'category' => 2, // Mobilier
            ],
        ];

        foreach ($annoncesData as $index => $data) {
            $annonce = new Annonce();
            $annonce->setTitle($data['title']);
            $annonce->setDescription($data['description']);
            $annonce->setType($data['type']);
            $annonce->setState(AnnonceState::PUBLISHED);
            $annonce->setCampus($data['campus']);
            $annonce->setCategory($categories[$data['category']]);
            $annonce->setOwner($users[$index % count($users)]);
            $annonce->setExpiresAt(new \DateTime('+30 days'));

            $manager->persist($annonce);
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
        // Créer un proxy pour permettre le hashage
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
