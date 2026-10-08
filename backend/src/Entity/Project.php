<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
// Public listing: WHERE is_published ORDER BY sort_order, id LIMIT n is read straight from this partial index
#[ORM\Index(name: 'project_published_order_idx', columns: ['sort_order', 'id'], options: ['where' => 'is_published'])]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $title;

    // Short summary for project cards
    #[ORM\Column(length: 500)]
    private string $description;

    // Full write-up in Markdown
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    // Path relative to the uploads directory
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    /** @var array<string, string> cover thumbnail size => path, filled by the image worker */
    #[ORM\Column(type: Types::JSONB)]
    private array $coverThumbnails = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $repoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $demoUrl = null;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    /** @var Collection<int, Technology> */
    #[ORM\ManyToMany(targetEntity: Technology::class)]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    private Collection $technologies;

    /** @var Collection<int, ProjectImage> */
    #[ORM\OneToMany(targetEntity: ProjectImage::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    private Collection $images;

    public function __construct(string $slug, string $title, string $description)
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->technologies = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): void
    {
        $this->coverImage = $coverImage;
    }

    /**
     * Sets a new cover (or none) and returns the files of the previous one, to delete after the commit.
     *
     * @return list<string>
     */
    public function replaceCover(?string $path): array
    {
        $previous = null === $this->coverImage ? [] : [$this->coverImage, ...array_values($this->coverThumbnails)];
        $this->coverImage = $path;
        $this->coverThumbnails = [];

        return $previous;
    }

    /**
     * @return array<string, string>
     */
    public function getCoverThumbnails(): array
    {
        return $this->coverThumbnails;
    }

    /**
     * @param array<string, string> $coverThumbnails
     */
    public function setCoverThumbnails(array $coverThumbnails): void
    {
        $this->coverThumbnails = $coverThumbnails;
    }

    /**
     * Every stored file of the project: cover, gallery images and all their thumbnails.
     *
     * @return list<string>
     */
    public function getUploadedFiles(): array
    {
        $files = null === $this->coverImage ? [] : [$this->coverImage, ...array_values($this->coverThumbnails)];
        foreach ($this->images as $image) {
            $files = [...$files, $image->getPath(), ...array_values($image->getThumbnails())];
        }

        return $files;
    }

    public function getRepoUrl(): ?string
    {
        return $this->repoUrl;
    }

    public function setRepoUrl(?string $repoUrl): void
    {
        $this->repoUrl = $repoUrl;
    }

    public function getDemoUrl(): ?string
    {
        return $this->demoUrl;
    }

    public function setDemoUrl(?string $demoUrl): void
    {
        $this->demoUrl = $demoUrl;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function publish(): void
    {
        $this->isPublished = true;
    }

    public function unpublish(): void
    {
        $this->isPublished = false;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Technology>
     */
    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    public function addTechnology(Technology $technology): void
    {
        if (!$this->technologies->contains($technology)) {
            $this->technologies->add($technology);
        }
    }

    public function removeTechnology(Technology $technology): void
    {
        $this->technologies->removeElement($technology);
    }

    /**
     * @return Collection<int, ProjectImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(string $path): ProjectImage
    {
        $image = new ProjectImage($this, $path, $this->images->count());
        $this->images->add($image);

        return $image;
    }

    public function removeImage(ProjectImage $image): void
    {
        $this->images->removeElement($image);
    }
}
