<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\Project;
use App\Service\UploadUrlGenerator;
use OpenApi\Attributes as OA;

/**
 * Project card in the list.
 */
final readonly class ProjectListItem
{
    /**
     * @param list<TechnologyRef> $technologies
     */
    public function __construct(
        #[OA\Property(example: 'portfolio')]
        public string $slug,
        #[OA\Property(example: 'Портфолио')]
        public string $title,
        public string $description,
        public ?string $coverImageUrl,
        public ?string $repoUrl,
        public ?string $demoUrl,
        public array $technologies,
    ) {
    }

    public static function fromEntity(Project $project, UploadUrlGenerator $uploads): self
    {
        $cover = $project->getCoverImage();

        return new self(
            $project->getSlug(),
            $project->getTitle(),
            $project->getDescription(),
            null === $cover ? null : $uploads->url($cover),
            $project->getRepoUrl(),
            $project->getDemoUrl(),
            TechnologyRef::listOf($project->getTechnologies()),
        );
    }
}
