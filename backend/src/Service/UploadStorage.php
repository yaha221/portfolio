<?php

declare(strict_types=1);

namespace App\Service;

use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Saves uploads to the "uploads.storage" Flysystem storage (public/uploads; in memory under test).
 * Paths are relative to the storage root; UploadUrlGenerator turns them into URLs.
 */
final readonly class UploadStorage
{
    public function __construct(
        private FilesystemOperator $uploadsStorage,
    ) {
    }

    public function store(UploadedFile $file, string $directory): string
    {
        // Extension from the detected MIME type, never from the client-supplied file name
        $path = \sprintf('%s/%s.%s', $directory, Uuid::v7()->toRfc4122(), $file->guessExtension() ?? 'bin');

        $stream = fopen($file->getPathname(), 'r');
        if (false === $stream) {
            throw new RuntimeException(\sprintf('Cannot read uploaded file "%s".', $file->getPathname()));
        }

        try {
            $this->uploadsStorage->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        return $path;
    }

    /**
     * @param iterable<string> $paths
     */
    public function delete(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->uploadsStorage->delete($path);
        }
    }
}
