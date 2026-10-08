<?php

declare(strict_types=1);

namespace App\Dto\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PageViewRequest
{
    public function __construct(
        #[OA\Property(description: 'Путь страницы SPA без query и hash', example: '/projects/portfolio')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Regex('~^/[^?#\s]*$~', message: 'Send the page path only, starting with "/".')]
        public string $path,
    ) {
    }
}
