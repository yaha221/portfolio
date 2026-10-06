<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Turns a stored upload path ("projects/abc.jpg") into a public URL.
 */
final readonly class UploadUrlGenerator
{
    public function __construct(
        #[Autowire(param: 'app.uploads_base_url')]
        private string $baseUrl,
    ) {
    }

    public function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }
}
