<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    public const int MIN_LEVEL = 1;
    public const int MAX_LEVEL = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    // Group on the site, e.g. "Backend", "DevOps"
    #[ORM\Column(length: 50)]
    private string $category;

    // 1–5, enforced by setLevel()
    #[ORM\Column(type: Types::SMALLINT)]
    private int $level;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function __construct(string $name, string $category, int $level)
    {
        $this->name = $name;
        $this->category = $category;
        $this->setLevel($level);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        if ($level < self::MIN_LEVEL || $level > self::MAX_LEVEL) {
            throw new InvalidArgumentException(\sprintf('Skill level must be between %d and %d, got %d.', self::MIN_LEVEL, self::MAX_LEVEL, $level));
        }

        $this->level = $level;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
