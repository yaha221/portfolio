<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PageViewRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageViewRepository::class)]
#[ORM\Index(name: 'page_view_created_at_idx', columns: ['created_at'])]
#[ORM\Index(name: 'page_view_path_idx', columns: ['path'])]
class PageView
{
    private const int USER_AGENT_MAX_LENGTH = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $path;

    // Salted hash of the visitor IP: counts unique visitors without storing the address
    #[ORM\Column(length: 64)]
    private string $ipHash;

    #[ORM\Column(length: self::USER_AGENT_MAX_LENGTH, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(string $path, string $ipHash, ?string $userAgent, ?DateTimeImmutable $createdAt = null)
    {
        $this->path = $path;
        $this->ipHash = $ipHash;
        $this->userAgent = null === $userAgent ? null : mb_substr($userAgent, 0, self::USER_AGENT_MAX_LENGTH);
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIpHash(): string
    {
        return $this->ipHash;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
