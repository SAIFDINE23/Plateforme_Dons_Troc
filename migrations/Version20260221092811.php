<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221092811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP moderated_campus');
        // Index rename - skip if doesn't exist
        // $this->addSql('ALTER INDEX idx_user_favorites_annonce RENAME TO IDX_E489ED118805AB2F');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD moderated_campus VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER INDEX idx_e489ed118805ab2f RENAME TO idx_user_favorites_annonce');
    }
}
