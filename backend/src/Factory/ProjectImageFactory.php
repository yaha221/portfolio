<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ProjectImage;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProjectImage>
 */
final class ProjectImageFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ProjectImage::class;
    }

    protected function defaults(): array
    {
        return [
            'project' => ProjectFactory::new(),
            'path' => 'projects/'.self::faker()->uuid().'.jpg',
            'position' => 0,
        ];
    }
}
