<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use OpenApi\Attributes as OA;

final readonly class PathStat
{
    public function __construct(
        #[OA\Property(example: '/projects/portfolio')]
        public string $path,
        #[OA\Property(example: 120)]
        public int $views,
    ) {
    }
}
