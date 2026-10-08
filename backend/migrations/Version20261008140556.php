<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008140556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Indexes from the stage 5 EXPLAIN review: partial index for the public project list, exact order index for contact messages, unused page_view.path index dropped';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX contact_message_created_at_idx');
        $this->addSql('CREATE INDEX contact_message_created_at_id_idx ON contact_message (created_at, id)');
        $this->addSql('DROP INDEX page_view_path_idx');
        $this->addSql('DROP INDEX project_published_sort_idx');
        $this->addSql('CREATE INDEX project_published_order_idx ON project (sort_order, id) WHERE is_published');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX contact_message_created_at_id_idx');
        $this->addSql('CREATE INDEX contact_message_created_at_idx ON contact_message (created_at)');
        $this->addSql('CREATE INDEX page_view_path_idx ON page_view (path)');
        $this->addSql('DROP INDEX project_published_order_idx');
        $this->addSql('CREATE INDEX project_published_sort_idx ON project (is_published, sort_order)');
    }
}
