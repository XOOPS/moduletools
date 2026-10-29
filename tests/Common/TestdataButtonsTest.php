<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\TestdataButtons;

#[CoversClass(TestdataButtons::class)]
final class TestdataButtonsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['xoopsSecurity']);
    }

    #[Test]
    public function buttonLinksCarryTheTokenWithARawAmpersand(): void
    {
        // ModuleAdmin::renderButton() HTML-escapes the link itself: an '&amp;' here reaches the
        // browser as '&amp;amp;' and the request as an "amp;XOOPS_TOKEN_REQUEST" parameter.
        $GLOBALS['xoopsSecurity'] = new class {
            public function createToken(): string
            {
                return 'tok';
            }
        };
        $admin = new class {
            /** @var list<string> */
            public array $links = [];

            public function addItemButton(string $title, string $link, string $icon = 'add'): bool
            {
                $this->links[] = $link;

                return true;
            }
        };
        $helper = $this->createStub(\Xmf\Module\Helper::class);
        $helper->method('dirname')->willReturn('mymodule');

        ob_start(); // the button style sheet
        try {
            TestdataButtons::loadButtonConfig($admin, $helper, 0); // 0: buttons hidden, only the "Show" toggle
        } finally {
            ob_end_clean();
        }

        self::assertSame(['?op=show_buttons&XOOPS_TOKEN_REQUEST=tok'], $admin->links);
    }
}
