<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;

/**
 * Error body in RFC 9457 format, served as application/problem+json.
 */
final readonly class ProblemDetails
{
    /**
     * @param list<Violation>|null $violations
     */
    public function __construct(
        #[OA\Property(example: 'Not Found')]
        public string $title,
        #[OA\Property(example: 404)]
        public int $status,
        public ?string $detail = null,
        #[OA\Property(description: 'Ошибки валидации, только для 400/422')]
        public ?array $violations = null,
        #[OA\Property(example: 'about:blank')]
        public string $type = 'about:blank',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'violations' => null === $this->violations ? null : array_map(
                static fn (Violation $violation) => ['propertyPath' => $violation->propertyPath, 'message' => $violation->message],
                $this->violations,
            ),
        ], static fn (mixed $value) => null !== $value);
    }
}
