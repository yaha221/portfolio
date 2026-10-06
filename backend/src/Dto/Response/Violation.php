<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

final readonly class Violation
{
    public function __construct(
        #[OA\Property(example: 'perPage')]
        public string $propertyPath,
        #[OA\Property(example: 'This value should be between 1 and 50.')]
        public string $message,
    ) {
    }
}
