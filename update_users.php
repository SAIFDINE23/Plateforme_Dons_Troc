<?php
// Script pour mettre à jour et créer des utilisateurs
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

$em = $container->get('doctrine.orm.default_entity_manager');

$factory = new PasswordHasherFactory([
    'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => [
        'algorithm' => 'bcrypt',
    ]
]);
$hasher = $factory->getPasswordHasher(User::class);

$userRepo = $em->getRepository(User::class);

// 1. Mettre à jour saif-eddine.el-khantache → skhantache
echo "=== Mise à jour du CAS UID de saif-eddine ===\n";
$saifUser = $userRepo->findOneBy(['email' => 'saif-eddine.el-khantache@etu.eilco.univ-littoral.fr']);
if ($saifUser) {
    $saifUser->setCasUid('skhantache');
    $em->persist($saifUser);
    echo "✅ CAS UID de saif-eddine mis à jour : skhantache\n";
} else {
    echo "❌ Utilisateur saif-eddine introuvable\n";
}

// 2. Créer oussama ismaili
echo "\n=== Création du nouvel utilisateur oussama ===\n";
$oussama_email = 'oussama.ismaili@etu.eilco.univ-littoral.fr';
$existingOussama = $userRepo->findOneBy(['email' => $oussama_email]);

if ($existingOussama) {
    echo "❌ L'utilisateur oussama existe déjà!\n";
} else {
    $oussama = new User();
    $oussama->setCasUid('oismaili');
    $oussama->setEmail($oussama_email);
    $oussama->setRoles(['ROLE_USER']);
    $oussama->setCreatedAt(new \DateTimeImmutable());
    $oussama->setIsBanned(false);
    
    $hashedPassword = $hasher->hash('00000000');
    $oussama->setPassword($hashedPassword);
    
    $em->persist($oussama);
    echo "✅ Utilisateur créé avec succès!\n";
    echo "   Email: $oussama_email\n";
    echo "   CAS UID: oismaili\n";
    echo "   Mot de passe: 00000000\n";
}

// Flush les deux opérations
$em->flush();
echo "\n✅ Tous les changements ont été enregistrés!\n";
