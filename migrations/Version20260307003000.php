<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Réduit la longueur maximale des alias à 20 caractères et normalise les alias existants';
    }

    public function up(Schema $schema): void
    {
        // Normaliser les alias trop longs vers un format pseudo-aléatoire <= 20 caractères
        $this->addSql("UPDATE \"user\" SET alias = LOWER('u' || SUBSTRING(MD5(id::text), 1, 10) || SUBSTRING(REPLACE(id::text, '-', ''), 1, 9)) WHERE alias IS NOT NULL AND LENGTH(alias) > 20");

        // Ajuster le schéma
        $this->addSql('ALTER TABLE "user" ALTER COLUMN alias TYPE VARCHAR(20)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER COLUMN alias TYPE VARCHAR(40)');
    }
}
