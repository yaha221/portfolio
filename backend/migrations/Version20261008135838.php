<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008135838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cover thumbnails of projects';
    }

    public function up(Schema $schema): void
    {
        // Default only to fill existing rows; the mapping has no default
        $this->addSql("ALTER TABLE project ADD cover_thumbnails JSONB DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE project ALTER cover_thumbnails DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP cover_thumbnails');
    }
}
