<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Dto\Response\TechnologyRef;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\Service\UploadUrlGenerator;
use DateTimeInterface;
use OpenApi\Attributes as OA;

final readonly class AdminProjectView
{
    /**
     * @param list<TechnologyRef>         $technologies
     * @param list<AdminProjectImageView> $images
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $description,
        public string $content,
        public ?string $coverImageUrl,
        public ?string $repoUrl,
        public ?string $demoUrl,
        public bool $isPublished,
        public int $sortOrder,
        public array $technologies,
        public array $images,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Project $project, UploadUrlGenerator $uploads): self
    {
        $cover = $project->getCoverImage();

        return new self(
            (int) $project->getId(),
            $project->getSlug(),
            $project->getTitle(),
            $project->getDescription(),
            $project->getContent(),
            null === $cover ? null : $uploads->url($cover),
            $project->getRepoUrl(),
            $project->getDemoUrl(),
            $project->isPublished(),
            $project->getSortOrder(),
            TechnologyRef::listOf($project->getTechnologies()),
            array_values(array_map(
                static fn (ProjectImage $image) => AdminProjectImageView::fromEntity($image, $uploads),
                $project->getImages()->toArray(),
            )),
            $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
