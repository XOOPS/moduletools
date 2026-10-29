<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Common\SysUtility;

defined('XOOPS_GROUP_ADMIN') || define('XOOPS_GROUP_ADMIN', '1');

final class FilesManagementTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/moduletools-fm-' . bin2hex(random_bytes(6));
        self::assertTrue(Filesystem::mkdir($this->dir . '/src/sub'));
        file_put_contents($this->dir . '/src/sub/a.txt', 'a');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['xoopsUser']);
        if (is_dir($this->dir)) {
            Filesystem::deleteDirectory($this->dir);
        }
    }

    public function testAModuleOnlyAdminCannotDeleteMoveOrCopy(): void
    {
        // isAdmin() is true for the CURRENT module's admin; that is not a webmaster.
        $GLOBALS['xoopsUser'] = new \XoopsUser(true, ['2', '4']);

        self::assertFalse(SysUtility::rcopy($this->dir . '/src', $this->dir . '/copy'));
        self::assertFalse(SysUtility::rmove($this->dir . '/src', $this->dir . '/moved'));
        self::assertFalse(SysUtility::rrmdir($this->dir . '/src'));
        self::assertFalse(SysUtility::deleteDirectory($this->dir . '/src'));
        self::assertFileExists($this->dir . '/src/sub/a.txt');
        self::assertDirectoryDoesNotExist($this->dir . '/copy');
    }

    public function testAWebmasterCanCopyAndDelete(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['2', '1']); // core returns ids as strings

        self::assertTrue(SysUtility::rcopy($this->dir . '/src', $this->dir . '/copy'));
        self::assertFileExists($this->dir . '/copy/sub/a.txt');
        self::assertTrue(SysUtility::rrmdir($this->dir . '/copy'));
        self::assertDirectoryDoesNotExist($this->dir . '/copy');
    }
}
