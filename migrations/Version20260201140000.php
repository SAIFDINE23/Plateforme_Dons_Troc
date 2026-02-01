<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user favorites relation (many-to-many)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_favorites (user_id UUID NOT NULL, annonce_id UUID NOT NULL, PRIMARY KEY(user_id, annonce_id))');
        $this->addSql('CREATE INDEX IDX_USER_FAVORITES_ANNONCE ON user_favorites (annonce_id)');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_USER_FAVORITES_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_USER_FAVORITES_ANNONCE FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_favorites');
    }
}
