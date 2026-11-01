<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Output;

#[CoversClass(Output::class)]
final class OutputTest extends TestCase
{
    public function testNavigationTargetAllowsHttpAndLocalPathsOnly(): void
    {
        self::assertSame('https://example.org/a?b=1', Output::navigationTarget('https://example.org/a?b=1'));
        self::assertSame('items.php?op=new', Output::navigationTarget('items.php?op=new'));
        self::assertSame('/modules/x/admin/', Output::navigationTarget('/modules/x/admin/'));

        foreach (['javascript:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)", 'JaVaScRiPt:x', 'data:text/html,x', '//evil.example', '\\\\evil'] as $url) {
            self::assertSame('index.php', Output::navigationTarget($url), $url);
        }
        self::assertSame('main.php', Output::navigationTarget('vbscript:x', 'main.php'));
    }
}
