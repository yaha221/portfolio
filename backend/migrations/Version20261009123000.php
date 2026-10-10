<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009123000 extends AbstractMigration
{
    // One duty per line: the about page keeps line breaks
    private const string OFFICEMAG_DUTIES = <<<'TEXT'
        — Разработка и поддержка крупного интернет-магазина на 1С-Битрикс (B2C и B2B).
        — Разработка и доработка компонентов и шаблонов: каталог, карточка товара, корзина, оформление заказа, личный кабинет.
        — Работа с инфоблоками, Highload-блоками и ORM D7; написание собственных модулей и обработчиков событий.
        — Обмен данными с 1С: настройка и доработка выгрузки каталога, цен, остатков и заказов.
        — Оптимизация производительности: анализ медленных SQL-запросов, индексы, кеширование компонентов.
        — Исправление ошибок и доработка legacy-кода, рефакторинг устаревших участков.
        — Работа с агентами и cron-задачами, импорт/экспорт данных (CSV, XML).
        — Командная разработка: Git, code review, оценка задач, взаимодействие с аналитиками и менеджерами.
        TEXT;

    // [company, position, start, end (null = current job), description] — from the resume of 10.10.2026.
    // Frozen here on purpose: later edits go through the admin, not by changing this migration.
    private const array JOBS = [
        ['ОФИСМАГ', 'PHP-разработчик', '2024-02-01', '2026-09-01', self::OFFICEMAG_DUTIES],
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
        // Company and start date only: the position or text may have been edited in the admin since
        foreach (self::JOBS as [$company, , $startDate]) {
            $this->addSql('DELETE FROM experience WHERE company = ? AND start_date = ?', [$company, $startDate]);
        }
    }
}
