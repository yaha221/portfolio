<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\PageView;
use PHPUnit\Framework\TestCase;

final class PageViewTest extends TestCase
{
    public function testLongUserAgentIsTruncatedToColumnLength(): void
    {
        $view = new PageView('/', str_repeat('a', 64), str_repeat('ю', 300));

        self::assertSame(255, mb_strlen((string) $view->getUserAgent()));
    }
}
