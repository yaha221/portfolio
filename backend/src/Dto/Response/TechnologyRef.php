<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\Technology;
use OpenApi\Attributes as OA;

/**
 * Technology as shown inside a project.
 */
final readonly class TechnologyRef
{
    public function __construct(
        #[OA\Property(example: 'symfony')]
        public string $slug,
        #[OA\Property(example: 'Symfony')]
        public string $name,
        #[OA\Property(description: 'Slug иконки в Simple Icons', example: 'symfony')]
        public ?string $icon,
    ) {
    }

    public static function fromEntity(Technology $technology): self
    {
        return new self($technology->getSlug(), $technology->getName(), $technology->getIcon());
    }

    /**
     * Sorted by name: a collection changed in memory keeps insertion order, not the mapping's OrderBy.
     *
     * @param iterable<Technology> $technologies
     *
     * @return list<self>
     */
    public static function listOf(iterable $technologies): array
    {
        $refs = [];
        foreach ($technologies as $technology) {
            $refs[] = self::fromEntity($technology);
        }
        usort($refs, static fn (self $a, self $b) => strnatcasecmp($a->name, $b->name));

        return $refs;
    }
}
