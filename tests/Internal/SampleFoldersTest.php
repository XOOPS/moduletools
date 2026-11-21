<?php declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Internal\SampleFolders;

defined('XOOPS_GROUP_ADMIN') || define('XOOPS_GROUP_ADMIN', '1');

final class SampleFoldersTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/moduletools-sample-' . bin2hex(random_bytes(6));
        self::assertTrue(Filesystem::mkdir($this->dir . '/module/testdata/images'));
        file_put_contents($this->dir . '/module/testdata/images/a.txt', 'a');
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['2', '1']); // a webmaster
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['xoopsUser']);
        if (is_dir($this->dir . '/module/locked')) {
            chmod($this->dir . '/module/locked', 0700);
        }
        if (is_dir($this->dir)) {
            Filesystem::deleteDirectory($this->dir);
        }
    }

    public function testAFolderTheModuleShipsIsCopied(): void
    {
        $failed = SampleFolders::copy([[$this->dir . '/module/testdata/images', $this->dir . '/uploads/images']]);

        self::assertSame([], $failed);
        self::assertFileExists($this->dir . '/uploads/images/a.txt');
    }

    public function testAFolderTheModuleDoesNotShipIsSkipped(): void
    {
        $failed = SampleFolders::copy([
            // the skeleton's entry, beside a testdata folder that holds other things
            [$this->dir . '/module/testdata/uploads', $this->dir . '/uploads'],
            // a module without the parent folder either
            [$this->dir . '/module/samples/deep/uploads', $this->dir . '/uploads'],
            [$this->dir . '/module/testdata/uploads/', $this->dir . '/uploads'],
        ]);

        self::assertSame([], $failed);
        self::assertDirectoryDoesNotExist($this->dir . '/uploads');
    }

    public function testARefusedCopyOfAnExistingFolderIsReported(): void
    {
        // A module-only admin is not a webmaster, so the copier refuses.
        $GLOBALS['xoopsUser'] = new \XoopsUser(true, ['2', '4']);
        $pair                 = [$this->dir . '/module/testdata/images', $this->dir . '/uploads/images'];

        self::assertSame([$pair], SampleFolders::copy([$pair]));
    }

    public function testAFileWhereAFolderIsExpectedIsReported(): void
    {
        $pair = [$this->dir . '/module/testdata/images/a.txt', $this->dir . '/uploads/images'];

        self::assertSame([$pair], SampleFolders::copy([$pair]));
    }

    public function testAPathTheCopierRefusesIsReportedAndNeverProbed(): void
    {
        // is_dir() throws on a NUL byte as of PHP 8.6 and would ask a stream wrapper about a URI.
        $pairs = [
            [$this->dir . "/module/testdata/up\0loads", $this->dir . '/uploads'],
            ['ftp://example.invalid/testdata/uploads', $this->dir . '/uploads'],
            ['', $this->dir . '/uploads'],
        ];

        self::assertSame($pairs, SampleFolders::copy($pairs));
    }

    public function testAFolderBelowAParentThatCannotBeSearchedIsReported(): void
    {
        if ('\\' === \DIRECTORY_SEPARATOR || (\function_exists('posix_geteuid') && 0 === posix_geteuid())) {
            self::markTestSkipped('Needs POSIX permissions and a user they apply to.');
        }
        self::assertTrue(Filesystem::mkdir($this->dir . '/module/locked/uploads'));
        $pair = [$this->dir . '/module/locked/uploads', $this->dir . '/uploads'];

        // Readable but not searchable: the entry is listed, is_dir() on it is false.
        chmod($this->dir . '/module/locked', 0400);
        clearstatcache();
        self::assertSame([$pair], SampleFolders::copy([$pair]));

        // Neither readable nor searchable: nothing confirms the folder is absent.
        chmod($this->dir . '/module/locked', 0000);
        clearstatcache();
        self::assertSame([$pair], SampleFolders::copy([$pair]));
    }
}
