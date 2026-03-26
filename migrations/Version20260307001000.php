<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un alias unique utilisateur et backfill les comptes existants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD alias VARCHAR(40) DEFAULT NULL');

        // Backfill: générer un alias unique pseudo-aléatoire basé sur l\'UUID
        $this->addSql("UPDATE \"user\" SET alias = LOWER('u' || REPLACE(id::text, '-', '')) WHERE alias IS NULL");

        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_ALIAS ON "user" (alias)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_ALIAS');
        $this->addSql('ALTER TABLE "user" DROP alias');
    }
}
