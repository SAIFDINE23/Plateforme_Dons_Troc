<?php
// Script pour créer un utilisateur via Symfony
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// Charger manuellement les variables d'environnement
if (file_exists(dirname(__FILE__).'/.env')) {
    $env_file = file_get_contents(dirname(__FILE__).'/.env');
    $lines = explode("\n", $env_file);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"');
        }
    }
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] ?? 'dev';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 1;

require_once dirname(__FILE__).'/vendor/autoload.php';
require_once dirname(__FILE__).'/src/Kernel.php';

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Utiliser la façon correcte d'accéder à Doctrine
$em = $container->get('doctrine.orm.default_entity_manager');

// Utiliser un hasher basique
$factory = new PasswordHasherFactory([
    'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => [
        'algorithm' => 'bcrypt',
    ]
]);
$hasher = $factory->getPasswordHasher(User::class);

$email = 'saif-eddine.el-khantache@etu.eilco.univ-littoral.fr';
$password = '00000000';
$casUid = str_replace('@etu.eilco.univ-littoral.fr', '', $email);

// Vérifier si l'utilisateur existe déjà
$userRepo = $em->getRepository(User::class);
$existingUser = $userRepo->findOneBy(['email' => $email]);

if ($existingUser) {
    echo "❌ L'utilisateur avec cet email existe déjà!\n";
    exit(1);
}

// Créer l'utilisateur
$user = new User();
$user->setCasUid($casUid);
$user->setEmail($email);
$user->setRoles(['ROLE_USER']);
$user->setCreatedAt(new \DateTimeImmutable());
$user->setIsBanned(false);

// Hasher le mot de passe
$hashedPassword = $hasher->hash($password);
$user->setPassword($hashedPassword);

// Persister et flush
$em->persist($user);
$em->flush();

echo "✅ Utilisateur créé avec succès!\n";
echo "   Email: $email\n";
echo "   CAS UID: $casUid\n";
echo "   Rôle: ROLE_USER\n";
echo "   Mot de passe: $password\n";
