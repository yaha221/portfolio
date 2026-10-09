<?php

declare(strict_types=1);

namespace App\Story;

use App\Factory\ContactMessageFactory;
use App\Factory\ExperienceFactory;
use App\Factory\PageViewFactory;
use App\Factory\ProjectFactory;
use App\Factory\SkillFactory;
use App\Factory\TechnologyFactory;
use App\Factory\UserFactory;
use DateTimeImmutable;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

/**
 * Development data set. Technologies mirror the project stack; skills and experience are real
 * (the same rows as migrations Version20261009140000 and Version20261009123000); other projects are placeholders.
 */
#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    // name => Simple Icons slug, also used as the technology slug
    private const array TECHNOLOGIES = [
        'PHP' => 'php',
        'Symfony' => 'symfony',
        'PostgreSQL' => 'postgresql',
        'RabbitMQ' => 'rabbitmq',
        'Docker' => 'docker',
        'Nginx' => 'nginx',
        'Vue.js' => 'vuedotjs',
        'TypeScript' => 'typescript',
        'Redis' => 'redis',
        'Git' => 'git',
    ];

    // [name, category, level 1–5] — the resume plus the stack of this project, as in migration Version20261009140000
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

    // [company, position, start, end (null = current job), description] — from the resume
    private const array EXPERIENCE = [
        ['ОФИСМАГ', 'Программист-стажер', '2024-02-01', null, 'Изучение Bitrix Framework. Поддержка проекта.'],
        ['Intrid', 'PHP-разработчик', '2023-09-01', '2024-02-01', 'Разработка программных продуктов.'],
        ['ЭФКО, Управляющая компания', 'Младший веб-программист', '2022-12-01', '2023-03-01', 'Настройка окружения, работа с докером, работа с git.'],
        ['Атос АйТи Солюшенс энд Сервисез', 'Программист-стажер', '2021-11-01', '2022-04-01', 'Обучение работе с C#, MSSQL, Sharepoint.'],
    ];

    public function build(): void
    {
        UserFactory::new()->admin()->create(['email' => 'admin@portfolio.local']);

        $technologies = [];
        foreach (self::TECHNOLOGIES as $name => $slug) {
            $technologies[$slug] = TechnologyFactory::createOne(['name' => $name, 'slug' => $slug, 'icon' => $slug]);
        }

        ProjectFactory::new()->published()->create([
            'slug' => 'portfolio',
            'title' => 'Портфолио',
            'description' => 'Этот сайт: REST API на Symfony, очередь RabbitMQ для фоновых задач и SPA на Vue.',
            'content' => "## Стек\n\nSymfony 7.4, PostgreSQL 17, RabbitMQ, Vue 3, Docker.\n\n## Что внутри\n\nРучные REST-контроллеры с DTO, JWT для админки, письма и превью картинок через Messenger.",
            'repoUrl' => null,
            'demoUrl' => null,
            'sortOrder' => 0,
            'technologies' => array_map(
                static fn (string $slug) => $technologies[$slug],
                ['php', 'symfony', 'postgresql', 'rabbitmq', 'docker', 'vuedotjs', 'typescript'],
            ),
        ]);
        ProjectFactory::new()->published()->many(5)->create(
            static fn () => ['technologies' => TechnologyFactory::randomRange(2, 5)],
        );
        // Draft: must not appear in the public API
        ProjectFactory::createOne(['title' => 'Черновик проекта', 'slug' => 'draft']);

        foreach (self::SKILLS as $position => [$name, $category, $level]) {
            SkillFactory::createOne(['name' => $name, 'category' => $category, 'level' => $level, 'sortOrder' => $position]);
        }

        foreach (self::EXPERIENCE as [$company, $position, $startDate, $endDate, $description]) {
            ExperienceFactory::createOne([
                'company' => $company,
                'position' => $position,
                'startDate' => new DateTimeImmutable($startDate),
                'endDate' => null === $endDate ? null : new DateTimeImmutable($endDate),
                'description' => $description,
            ]);
        }

        ContactMessageFactory::createMany(5);
        PageViewFactory::createMany(200);
    }
}
