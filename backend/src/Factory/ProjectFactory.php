<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Project;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Project>
 */
final class ProjectFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Project::class;
    }

    public function published(): self
    {
        return $this->afterInstantiate(static fn (Project $project) => $project->publish());
    }

    protected function defaults(): array
    {
        // sentence() is lorem ipsum in every locale; realText() follows the ru_RU locale
        $title = rtrim(self::faker()->unique()->realTextBetween(15, 40), '.,!?;: ');
        $slug = new AsciiSlugger('ru')->slug($title)->lower()->toString();

        return [
            'slug' => $slug,
            'title' => $title,
            'description' => self::faker()->realText(200),
            'content' => self::faker()->realText(1500),
            'repoUrl' => 'https://github.com/example/'.$slug,
            'demoUrl' => self::faker()->boolean() ? 'https://'.$slug.'.example.com' : null,
            'sortOrder' => self::faker()->numberBetween(1, 100),
        ];
    }
}
