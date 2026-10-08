<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UploadStorage;
use App\Tests\Support\TestImage;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadStorageTest extends TestCase
{
    private Filesystem $filesystem;

    private string $tempFile;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->tempFile = (string) tempnam(sys_get_temp_dir(), 'upload');
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testNamesTheFileByItsContentNotByTheClientSuppliedName(): void
    {
        // A PNG sent as "shell.php" must not end up as an executable script under public/uploads
        file_put_contents($this->tempFile, TestImage::png(10, 10));

        $path = $this->storage()->store(new UploadedFile($this->tempFile, 'shell.php', 'application/x-php', test: true), 'projects');

        self::assertMatchesRegularExpression('~^projects/[0-9a-f-]{36}\.png$~', $path);
        self::assertSame(TestImage::png(10, 10), $this->filesystem->read($path));
    }

    public function testEveryUploadGetsItsOwnName(): void
    {
        file_put_contents($this->tempFile, TestImage::png(10, 10));
        $file = new UploadedFile($this->tempFile, 'cover.png', test: true);

        self::assertNotSame($this->storage()->store($file, 'projects'), $this->storage()->store($file, 'projects'));
    }

    public function testDeletesTheGivenFilesOnly(): void
    {
        $this->filesystem->write('projects/a.png', 'a');
        $this->filesystem->write('projects/b.png', 'b');
        $this->filesystem->write('projects/c.png', 'c');

        $this->storage()->delete(['projects/a.png', 'projects/b.png']);

        self::assertFalse($this->filesystem->fileExists('projects/a.png'));
        self::assertFalse($this->filesystem->fileExists('projects/b.png'));
        self::assertTrue($this->filesystem->fileExists('projects/c.png'));
    }

    private function storage(): UploadStorage
    {
        return new UploadStorage($this->filesystem);
    }
}
