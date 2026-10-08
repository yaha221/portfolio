<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Project;
use App\Message\ProjectPublished;
use App\MessageHandler\NotifyTelegramAboutPublishedProject;
use App\Repository\ProjectRepository;
use App\Service\Notification\AdminChat;
use App\Tests\Support\SpyChatter;
use PHPUnit\Framework\TestCase;

final class NotifyTelegramAboutPublishedProjectTest extends TestCase
{
    public function testSendsTitleAndLinkToTheProjectPage(): void
    {
        $project = new Project('portfolio', 'Портфолио', 'Этот сайт');
        $project->publish();
        $chatter = new SpyChatter();

        $this->handler($project, $chatter, 'https://example.com/')(new ProjectPublished(5));

        self::assertSame("Опубликован проект «Портфолио»\nhttps://example.com/projects/portfolio", $chatter->onlyMessage()->getSubject());
    }

    public function testProjectUnpublishedBeforeTheWorkerRanIsSkipped(): void
    {
        $chatter = new SpyChatter();

        $this->handler(new Project('portfolio', 'Портфолио', 'Этот сайт'), $chatter)(new ProjectPublished(5));

        self::assertSame([], $chatter->sent);
    }

    public function testDeletedProjectIsSkipped(): void
    {
        $chatter = new SpyChatter();

        $this->handler(null, $chatter)(new ProjectPublished(5));

        self::assertSame([], $chatter->sent);
    }

    private function handler(?Project $project, SpyChatter $chatter, string $siteUrl = 'https://example.com'): NotifyTelegramAboutPublishedProject
    {
        $projects = $this->createMock(ProjectRepository::class);
        $projects->expects(self::once())->method('find')->with(5)->willReturn($project);

        return new NotifyTelegramAboutPublishedProject($projects, new AdminChat($chatter), $siteUrl);
    }
}
