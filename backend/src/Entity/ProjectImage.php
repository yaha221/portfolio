<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectImageRepository::class)]
class ProjectImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    // Original upload, relative to the uploads directory
    #[ORM\Column(length: 255)]
    private string $path;

    /** @var array<string, string> thumbnail size => path, filled by the image worker */
    #[ORM\Column(type: Types::JSONB)]
    private array $thumbnails = [];

    #[ORM\Column]
    private int $position;

    public function __construct(Project $project, string $path, int $position)
    {
        $this->project = $project;
        $this->path = $path;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string>
     */
    public function getThumbnails(): array
    {
        return $this->thumbnails;
    }

    /**
     * @param array<string, string> $thumbnails
     */
    public function setThumbnails(array $thumbnails): void
    {
        $this->thumbnails = $thumbnails;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
