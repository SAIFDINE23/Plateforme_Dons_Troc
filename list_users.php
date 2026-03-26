<?php
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=plateforme_dons_troc', 'plateforme_user', 'password123');
$stmt = $pdo->query('SELECT cas_uid, email, roles::text, is_banned FROM "user" ORDER BY cas_uid');
echo str_pad("CAS UID", 30) . str_pad("EMAIL", 45) . str_pad("ROLES", 30) . "STATUT\n";
echo str_repeat("-", 115) . "\n";
foreach ($stmt as $row) {
    echo str_pad($row['cas_uid'], 30)
        . str_pad($row['email'], 45)
        . str_pad($row['roles'], 30)
        . ($row['is_banned'] ? 'BANNI' : 'ACTIF') . "\n";
}
