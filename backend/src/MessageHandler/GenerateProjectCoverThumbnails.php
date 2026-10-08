<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProjectCoverUploaded;
use App\Repository\ProjectRepository;
use App\Service\Image\ThumbnailGenerator;
use App\Service\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final readonly class GenerateProjectCoverThumbnails
{
    public function __construct(
        private ProjectRepository $projects,
        private ThumbnailGenerator $generator,
        private UploadStorage $storage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ProjectCoverUploaded $event): void
    {
        $project = $this->projects->find($event->projectId);
        // Project deleted, or the cover was replaced/removed before the worker got to it
        if (null === $project || $project->getCoverImage() !== $event->coverPath) {
            return;
        }

        try {
            $thumbnails = $this->generator->generate($event->coverPath);
        } catch (UnableToReadFile|InvalidArgumentException $exception) {
            throw new UnrecoverableMessageHandlingException($exception->getMessage(), previous: $exception);
        }

        $previous = $project->getCoverThumbnails();
        $project->setCoverThumbnails($thumbnails);
        $this->entityManager->flush();

        $this->storage->delete(array_diff($previous, $thumbnails));
    }
}
