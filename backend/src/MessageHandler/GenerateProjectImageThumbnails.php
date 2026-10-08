<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProjectImageUploaded;
use App\Repository\ProjectImageRepository;
use App\Service\Image\ThumbnailGenerator;
use App\Service\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final readonly class GenerateProjectImageThumbnails
{
    public function __construct(
        private ProjectImageRepository $images,
        private ThumbnailGenerator $generator,
        private UploadStorage $storage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ProjectImageUploaded $event): void
    {
        $image = $this->images->find($event->imageId);
        if (null === $image) {
            return; // deleted before the worker got to it
        }

        try {
            $thumbnails = $this->generator->generate($image->getPath());
        } catch (UnableToReadFile|InvalidArgumentException $exception) {
            // Missing or broken file: retrying won't help
            throw new UnrecoverableMessageHandlingException($exception->getMessage(), previous: $exception);
        }

        $previous = $image->getThumbnails();
        $image->setThumbnails($thumbnails);
        $this->entityManager->flush();

        // Same names are overwritten in place; anything else left from an earlier size set goes
        $this->storage->delete(array_diff($previous, $thumbnails));
    }
}
