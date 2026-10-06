<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

final readonly class PaginationMeta
{
    public function __construct(
        #[OA\Property(example: 1)]
        public int $page,
        #[OA\Property(example: 12)]
        public int $perPage,
        #[OA\Property(description: 'Всего элементов по фильтру', example: 6)]
        public int $total,
        #[OA\Property(example: 1)]
        public int $pages,
    ) {
    }

    public static function of(int $page, int $perPage, int $total): self
    {
        return new self($page, $perPage, $total, (int) ceil($total / $perPage));
    }
}
