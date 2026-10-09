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
 * Development data set. Technologies and skills mirror the project stack, experience is real
 * (the same rows as migration Version20261009123000); skill levels and other projects are placeholders.
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

    // [name, category, level 1–5]
    private const array SKILLS = [
        ['PHP', 'Backend', 5],
        ['Symfony', 'Backend', 5],
        ['PostgreSQL', 'Backend', 4],
        ['RabbitMQ', 'Backend', 4],
        ['Vue.js', 'Frontend', 3],
        ['TypeScript', 'Frontend', 3],
        ['Docker', 'DevOps', 4],
        ['CI/CD', 'DevOps', 3],
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
