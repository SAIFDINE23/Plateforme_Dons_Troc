<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228113530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Ajouter la nouvelle colonne campuses comme nullable temporairement
        $this->addSql('ALTER TABLE annonce ADD campuses JSON DEFAULT NULL');
        
        // Migrer les données existantes : convertir campus en tableau campuses
        $this->addSql("UPDATE annonce SET campuses = json_build_array(campus)::jsonb");
        
        // Rendre la colonne NOT NULL maintenant qu'elle a des données
        $this->addSql('ALTER TABLE annonce ALTER COLUMN campuses SET NOT NULL');
        
        // Supprimer l'ancienne colonne campus
        $this->addSql('ALTER TABLE annonce DROP campus');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // Ajouter l'ancienne colonne campus comme nullable temporairement
        $this->addSql('ALTER TABLE annonce ADD campus VARCHAR(255) DEFAULT NULL');
        
        // Migrer les données : prendre le premier campus du tableau
        $this->addSql("UPDATE annonce SET campus = campuses->>0");
        
        // Rendre la colonne NOT NULL maintenant qu'elle a des données
        $this->addSql('ALTER TABLE annonce ALTER COLUMN campus SET NOT NULL');
        
        // Supprimer la nouvelle colonne campuses
        $this->addSql('ALTER TABLE annonce DROP campuses');
    }
}
