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

    public function testReplacingTheCoverReturnsTheOldFilesToDelete(): void
    {
        $project = new Project('demo', 'Demo', 'Description');
        self::assertSame([], $project->replaceCover('projects/first.png'), 'No cover yet: nothing to delete');
        $project->setCoverThumbnails(['small' => 'projects/first-small.webp', 'large' => 'projects/first-large.webp']);

        $previous = $project->replaceCover('projects/second.png');

        self::assertSame(['projects/first.png', 'projects/first-small.webp', 'projects/first-large.webp'], $previous);
        self::assertSame('projects/second.png', $project->getCoverImage());
        self::assertSame([], $project->getCoverThumbnails(), 'Thumbnails of the new cover come from the worker');
    }

    public function testListsEveryStoredFile(): void
    {
        $project = new Project('demo', 'Demo', 'Description');
        $project->setCoverImage('cover.png');
        $project->setCoverThumbnails(['small' => 'cover-small.webp']);
        $project->addImage('a.png')->setThumbnails(['small' => 'a-small.webp', 'large' => 'a-large.webp']);
        $project->addImage('b.png')->setThumbnails(['small' => 'b-small.webp']);

        self::assertSame(
            ['cover.png', 'cover-small.webp', 'a.png', 'a-small.webp', 'a-large.webp', 'b.png', 'b-small.webp'],
            $project->getUploadedFiles(),
        );
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
