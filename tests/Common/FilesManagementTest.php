<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Common\SysUtility;
use Xoops\ModuleTools\Internal\PathContainment;

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
        // A second copy replaces existing files (the destination is unlinked, then created exclusively).
        file_put_contents($this->dir . '/src/sub/a.txt', 'changed');
        self::assertTrue(SysUtility::rcopy($this->dir . '/src', $this->dir . '/copy'));
        self::assertSame('changed', file_get_contents($this->dir . '/copy/sub/a.txt'));
        self::assertTrue(SysUtility::rrmdir($this->dir . '/copy'));
        self::assertDirectoryDoesNotExist($this->dir . '/copy');
    }

    public function testACopyIntoItsOwnSourceIsRefused(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['1']);

        self::assertFalse(SysUtility::rcopy($this->dir . '/src', $this->dir . '/src/sub/copy'));
        self::assertDirectoryDoesNotExist($this->dir . '/src/sub/copy');
        // A destination above the source would receive the source itself, then rmove() deletes it.
        self::assertFalse(SysUtility::rmove($this->dir . '/src/sub', $this->dir . '/src'));
        self::assertFileExists($this->dir . '/src/sub/a.txt');
        self::assertFalse(SysUtility::xcopy($this->dir . '/src', $this->dir . '/src/copy'));
    }

    public function testALinkedDestinationIsNeverWrittenThrough(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['1']);
        self::assertTrue(Filesystem::mkdir($this->dir . '/elsewhere'));
        if (!@symlink($this->dir . '/elsewhere', $this->dir . '/dest')) {
            self::markTestSkipped('symlink() is not permitted here');
        }

        self::assertFalse(SysUtility::rcopy($this->dir . '/src', $this->dir . '/dest'));
        self::assertFalse(SysUtility::rcopy($this->dir . '/src', $this->dir . '/dest/.'));
        self::assertDirectoryDoesNotExist($this->dir . '/elsewhere/sub');

        // The single-file branch of xcopy() must not write through a linked destination either.
        file_put_contents($this->dir . '/elsewhere/target.txt', 'original');
        if (!@symlink($this->dir . '/elsewhere/target.txt', $this->dir . '/file-link.txt')) {
            self::markTestSkipped('symlink() is not permitted here');
        }
        self::assertFalse(SysUtility::xcopy($this->dir . '/src/sub/a.txt', $this->dir . '/file-link.txt'));
        self::assertSame('original', file_get_contents($this->dir . '/elsewhere/target.txt'));
    }

    public function testATrailingSeparatorCannotHideALinkedMoveSource(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['1']);
        if (!@symlink($this->dir . '/src', $this->dir . '/linked')) {
            self::markTestSkipped('symlink() is not permitted here');
        }

        self::assertFalse(SysUtility::rmove($this->dir . '/linked/', $this->dir . '/moved'));
        self::assertFalse(SysUtility::rmove($this->dir . '/linked/.', $this->dir . '/moved'));
        self::assertFileExists($this->dir . '/src/sub/a.txt');
    }

    public function testAWindowsJunctionIsTreatedAsALink(): void
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            self::markTestSkipped('Junctions exist only on Windows');
        }
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['1']);
        self::assertTrue(Filesystem::mkdir($this->dir . '/outside'));
        file_put_contents($this->dir . '/outside/secret.txt', 'secret');
        $junction = str_replace('/', '\\', $this->dir . '/src/sub/junction');
        exec('cmd /c mklink /J ' . escapeshellarg($junction) . ' ' . escapeshellarg(str_replace('/', '\\', $this->dir . '/outside')), $output, $code);
        if (0 !== $code) {
            self::markTestSkipped('mklink /J failed');
        }

        try {
            // is_dir() on a junction varies between calls on PHP for Windows, so the detection
            // itself is pinned; the move below must then refuse whatever is_dir() says.
            self::assertFalse(is_link($junction));
            self::assertTrue(PathContainment::isLink($junction));
            self::assertFalse(SysUtility::rmove($this->dir . '/src', $this->dir . '/moved'));
            self::assertFileExists($this->dir . '/outside/secret.txt');
            self::assertFileDoesNotExist($this->dir . '/moved/sub/junction/secret.txt');
        } finally {
            @rmdir($junction); // removes the junction only, never its target
        }
    }

    public function testANestedLinkFailsTheCopyAndKeepsTheMoveSource(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['1']);
        file_put_contents($this->dir . '/secret.txt', 'secret');
        if (!@symlink($this->dir . '/secret.txt', $this->dir . '/src/sub/link.txt')) {
            self::markTestSkipped('symlink() is not permitted here');
        }

        self::assertFalse(SysUtility::rmove($this->dir . '/src', $this->dir . '/moved'));
        self::assertTrue(is_link($this->dir . '/src/sub/link.txt'));
        self::assertFileDoesNotExist($this->dir . '/moved/sub/link.txt');
        self::assertFalse(SysUtility::xcopy($this->dir . '/src', $this->dir . '/copy'));
    }
}
