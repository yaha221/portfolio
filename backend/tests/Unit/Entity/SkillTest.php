<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Skill;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class SkillTest extends TestCase
{
    #[TestWith([0])]
    #[TestWith([6])]
    public function testRejectsLevelOutsideRange(int $level): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Skill('PHP', 'Backend', $level);
    }

    public function testAcceptsBoundaryLevels(): void
    {
        $skill = new Skill('PHP', 'Backend', Skill::MIN_LEVEL);
        $skill->setLevel(Skill::MAX_LEVEL);

        self::assertSame(Skill::MAX_LEVEL, $skill->getLevel());
    }
}
