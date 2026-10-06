<?php

declare(strict_types=1);

namespace App\Dto\Request\Admin;

use DateTimeImmutable;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dates are YYYY-MM-DD strings: Assert\Date gives a clear error for a bad value,
 * and such strings compare correctly as plain text.
 */
final readonly class ExperienceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $company,
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $position,
        #[OA\Property(format: 'date', example: '2023-01-18')]
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $startDate,
        #[OA\Property(format: 'date', description: 'null — текущее место работы', example: null)]
        #[Assert\Date]
        #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'End date must not be before the start date.')]
        public ?string $endDate = null,
        public string $description = '',
    ) {
    }

    // Not named startDate()/endDate(): PropertyAccessor would pick such methods up for propertyPath: 'startDate'
    public function toStartDate(): DateTimeImmutable
    {
        return self::toDate($this->startDate);
    }

    public function toEndDate(): ?DateTimeImmutable
    {
        return null === $this->endDate ? null : self::toDate($this->endDate);
    }

    private static function toDate(string $value): DateTimeImmutable
    {
        // "!" resets the time part; the value already passed Assert\Date
        return DateTimeImmutable::createFromFormat('!Y-m-d', $value)
            ?: throw new LogicException(\sprintf('"%s" is not a Y-m-d date.', $value));
    }
}
