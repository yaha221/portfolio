<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\Project;
use App\Service\UploadUrlGenerator;
use DateTimeInterface;
use OpenApi\Attributes as OA;

final readonly class ProjectDetail
{
    /**
     * @param list<TechnologyRef>    $technologies
     * @param list<ProjectImageView> $images
     */
    public function __construct(
        #[OA\Property(example: 'portfolio')]
        public string $slug,
        #[OA\Property(example: 'Портфолио')]
        public string $title,
        public string $description,
        #[OA\Property(description: 'Полное описание в Markdown')]
        public string $content,
        public ?string $coverImageUrl,
        public ?string $repoUrl,
        public ?string $demoUrl,
        public array $technologies,
        public array $images,
        #[OA\Property(format: 'date-time', example: '2026-10-06T12:00:00+00:00')]
        public string $createdAt,
        #[OA\Property(format: 'date-time', example: '2026-10-06T12:00:00+00:00')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Project $project, UploadUrlGenerator $uploads): self
    {
        $cover = $project->getCoverImage();

        return new self(
            $project->getSlug(),
            $project->getTitle(),
            $project->getDescription(),
            $project->getContent(),
            null === $cover ? null : $uploads->url($cover),
            $project->getRepoUrl(),
            $project->getDemoUrl(),
            array_values(array_map(TechnologyRef::fromEntity(...), $project->getTechnologies()->toArray())),
            array_values(array_map(
                static fn ($image) => ProjectImageView::fromEntity($image, $uploads),
                $project->getImages()->toArray(),
            )),
            $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
