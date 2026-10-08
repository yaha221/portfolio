<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProjectPublished;
use App\Repository\ProjectRepository;
use App\Service\Notification\AdminChat;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class NotifyTelegramAboutPublishedProject
{
    public function __construct(
        private ProjectRepository $projects,
        private AdminChat $chat,
        #[Autowire(param: 'app.site_url')]
        private string $siteUrl,
    ) {
    }

    public function __invoke(ProjectPublished $event): void
    {
        $project = $this->projects->find($event->projectId);
        // Deleted or unpublished again before the worker got to it
        if (null === $project || !$project->isPublished()) {
            return;
        }

        $this->chat->send(\sprintf(
            "Опубликован проект «%s»\n%s/projects/%s",
            $project->getTitle(),
            rtrim($this->siteUrl, '/'),
            $project->getSlug(),
        ));
    }
}
