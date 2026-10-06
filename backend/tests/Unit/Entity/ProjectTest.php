<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Project;
use App\Entity\Technology;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testNewProjectIsDraftUntilPublished(): void
    {
        $project = new Project('demo', 'Demo', 'Description');
        self::assertFalse($project->isPublished());

        $project->publish();
        self::assertTrue($project->isPublished());
    }

    public function testImagesGetSequentialPositions(): void
    {
        $project = new Project('demo', 'Demo', 'Description');

        $first = $project->addImage('a.jpg');
        $second = $project->addImage('b.jpg');

        self::assertSame([0, 1], [$first->getPosition(), $second->getPosition()]);
        self::assertSame($project, $second->getProject());
        self::assertCount(2, $project->getImages());
    }

    public function testTechnologyIsAddedOnce(): void
    {
        $project = new Project('demo', 'Demo', 'Description');
        $php = new Technology('PHP', 'php');

        $project->addTechnology($php);
        $project->addTechnology($php);

        self::assertCount(1, $project->getTechnologies());
    }
}
