<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009140000 extends AbstractMigration
{
    // [name, category, level 1–5] in display order: the skills of the resume of 18.09.2026 plus the stack
    // of this project. Levels are the owner's self-assessment; later edits go through the admin.
    private const array SKILLS = [
        ['PHP', 'Backend', 4],
        ['Yii2', 'Backend', 3],
        ['Bitrix Framework', 'Backend', 3],
        ['Symfony', 'Backend', 3],
        ['RabbitMQ', 'Backend', 2],
        ['Redis', 'Backend', 2],
        ['PostgreSQL', 'Базы данных', 3],
        ['MySQL', 'Базы данных', 3],
        ['MS SQL Server', 'Базы данных', 2],
        ['HTML5', 'Frontend', 3],
        ['CSS3', 'Frontend', 3],
        ['Ajax', 'Frontend', 3],
        ['Vue.js', 'Frontend', 2],
        ['TypeScript', 'Frontend', 2],
        ['Git', 'Инструменты', 4],
        ['Docker', 'Инструменты', 3],
        ['Docker Compose', 'Инструменты', 3],
        ['Linux', 'Инструменты', 3],
        ['Nginx', 'Инструменты', 2],
        ['C#', '.NET', 2],
        ['.NET Framework', '.NET', 2],
    ];

    public function getDescription(): string
    {
        return 'Skills from the resume plus the stack of this project';
    }

    public function up(Schema $schema): void
    {
        foreach (self::SKILLS as $sortOrder => [$name, $category, $level]) {
            // A database filled by the development fixtures already has some of these names
            $this->addSql(
                'INSERT INTO skill (name, category, level, sort_order) VALUES (?, ?, ?, ?)
                 ON CONFLICT (name) DO UPDATE SET category = EXCLUDED.category, level = EXCLUDED.level, sort_order = EXCLUDED.sort_order',
                [$name, $category, $level, $sortOrder],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::SKILLS as [$name]) {
            $this->addSql('DELETE FROM skill WHERE name = ?', [$name]);
        }
    }
}
