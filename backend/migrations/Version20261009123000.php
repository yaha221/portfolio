<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009123000 extends AbstractMigration
{
    // [company, position, start, end (null = current job), description] — from the resume of 18.09.2026.
    // Frozen here on purpose: later edits go through the admin, not by changing this migration.
    private const array JOBS = [
        ['ОФИСМАГ', 'Программист-стажер', '2024-02-01', null, 'Изучение Bitrix Framework. Поддержка проекта.'],
        ['Intrid', 'PHP-разработчик', '2023-09-01', '2024-02-01', 'Разработка программных продуктов.'],
        ['ЭФКО, Управляющая компания', 'Младший веб-программист', '2022-12-01', '2023-03-01', 'Настройка окружения, работа с докером, работа с git.'],
        ['Атос АйТи Солюшенс энд Сервисез', 'Программист-стажер', '2021-11-01', '2022-04-01', 'Обучение работе с C#, MSSQL, Sharepoint.'],
    ];

    public function getDescription(): string
    {
        return 'Work experience from the resume';
    }

    public function up(Schema $schema): void
    {
        foreach (self::JOBS as [$company, $position, $startDate, $endDate, $description]) {
            $this->addSql(
                'INSERT INTO experience (company, position, start_date, end_date, description) VALUES (?, ?, ?, ?, ?)',
                [$company, $position, $startDate, $endDate, $description],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::JOBS as [$company, $position, $startDate]) {
            $this->addSql(
                'DELETE FROM experience WHERE company = ? AND position = ? AND start_date = ?',
                [$company, $position, $startDate],
            );
        }
    }
}
