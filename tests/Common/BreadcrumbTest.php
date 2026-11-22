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

        $breadcrumb = new Breadcrumb();

        self::assertSame('tag', $breadcrumb->dirname);
        self::assertSame('tag_common_breadcrumb.tpl', self::templateOf($breadcrumb));
    }

    public function testAGivenDirnameWinsOverTheModuleBeingServed(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        $breadcrumb = new Breadcrumb('publisher');

        self::assertSame('publisher', $breadcrumb->dirname);
        self::assertSame('publisher_common_breadcrumb.tpl', self::templateOf($breadcrumb));
        self::assertSame('publisher', (new Breadcrumb('/site/modules/publisher'))->dirname);
    }

    public function testADirnameOfZeroIsAGivenDirname(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        self::assertSame('0', (new Breadcrumb('0'))->dirname);
    }

    public function testAnEmptyDirnameTakesTheModuleBeingServed(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        self::assertSame('tag', (new Breadcrumb(''))->dirname);
    }

    public function testAGivenTemplateWinsOverTheDefault(): void
    {
        $GLOBALS['xoopsModule'] = new \XoopsModule('tag');

        self::assertSame('tag_trail.tpl', self::templateOf(new Breadcrumb(null, 'tag_trail.tpl')));
    }

    public function testWithoutAModuleItKeepsTheLibraryFolderName(): void
    {
        unset($GLOBALS['xoopsModule']);

        self::assertSame(\basename(\dirname(__DIR__, 2)), (new Breadcrumb())->dirname);
    }

    /** The template render() fetches; it is private, and render() needs the core's template engine. */
    private static function templateOf(Breadcrumb $breadcrumb): string
    {
        return (string)(new \ReflectionProperty(Breadcrumb::class, 'template'))->getValue($breadcrumb);
    }
}
