<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Breadcrumb;

final class BreadcrumbTest extends TestCase
{
    private bool $hadModule = false;
    private mixed $module   = null;

    protected function setUp(): void
    {
        $this->hadModule = \array_key_exists('xoopsModule', $GLOBALS);
        $this->module    = $GLOBALS['xoopsModule'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->hadModule) {
            $GLOBALS['xoopsModule'] = $this->module;
        } else {
            unset($GLOBALS['xoopsModule']);
        }
    }

    public function testWithoutADirnameItTakesTheModuleBeingServed(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        self::assertSame('tag', (new Breadcrumb())->dirname);
    }

    public function testAGivenDirnameWinsOverTheModuleBeingServed(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        self::assertSame('publisher', (new Breadcrumb('publisher'))->dirname);
        self::assertSame('publisher', (new Breadcrumb('/site/modules/publisher'))->dirname);
    }

    public function testWithoutAModuleItKeepsTheLibraryFolderName(): void
    {
        unset($GLOBALS['xoopsModule']);

        self::assertSame(\basename(\dirname(__DIR__, 2)), (new Breadcrumb())->dirname);
    }
}
