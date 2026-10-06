<?php

declare(strict_types=1);

namespace App\Dto\Request\Admin;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Quick changes from the project list: publish toggle and drag-and-drop order.
 * A field left out (null) stays as it is.
 */
final readonly class ProjectPatchRequest
{
    public function __construct(
        #[OA\Property(example: true)]
        public ?bool $isPublished = null,
        #[OA\Property(example: 3)]
        #[Assert\PositiveOrZero]
        public ?int $sortOrder = null,
    ) {
    }
}
