<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UploadUrlGenerator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class UploadUrlGeneratorTest extends TestCase
{
    #[TestWith(['https://example.com/uploads', 'projects/a.png'])]
    #[TestWith(['https://example.com/uploads/', 'projects/a.png'])]
    #[TestWith(['https://example.com/uploads', '/projects/a.png'])]
    #[TestWith(['https://example.com/uploads//', '//projects/a.png'])]
    public function testJoinsBaseAndPathWithExactlyOneSlash(string $baseUrl, string $path): void
    {
        self::assertSame('https://example.com/uploads/projects/a.png', new UploadUrlGenerator($baseUrl)->url($path));
    }
}
