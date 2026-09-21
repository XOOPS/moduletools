<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Admin\Utility;

final class UtilityTest extends TestCase
{
    public function testLegacyCollapsibleHelpersRenderEscapedAccessibleMarkup(): void
    {
        ob_start();
        Utility::getCollapsableBar('unsafe" id', '<Title>', '<script>alert(1)</script>');
        Utility::closeCollapsable('unsafe" id');
        $html = (string) ob_get_clean();

        self::assertStringContainsString('id="unsafe__id"', $html);
        self::assertStringContainsString('aria-controls="unsafe__id"', $html);
        self::assertStringContainsString('&lt;Title&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringEndsWith("</div>\n", $html);
    }
}
