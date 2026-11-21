<?php declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Module;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Common\Configurator;
use Xoops\ModuleTools\Module\Installer;

defined('XOOPS_GROUP_ADMIN') || define('XOOPS_GROUP_ADMIN', '1');

final class InstallerTestFoldersTest extends TestCase
{
    private string $dir;

    /** @var list<string> */
    private array $warnings = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/moduletools-installer-' . bin2hex(random_bytes(6));
        self::assertTrue(Filesystem::mkdir($this->dir . '/module/config'));
        self::assertTrue(Filesystem::mkdir($this->dir . '/module/testdata/images'));
        file_put_contents($this->dir . '/module/testdata/images/a.txt', 'a');

        // The skeleton's config: one sample folder the module ships, one it does not.
        $config = ['copyTestFolders' => [
            [$this->dir . '/module/testdata/uploads', $this->dir . '/uploads'],
            [$this->dir . '/module/testdata/images', $this->dir . '/uploads/images'],
        ]];
        file_put_contents($this->dir . '/module/config/config.php', '<?php return ' . var_export($config, true) . ';');

        $this->warnings = [];
        set_error_handler(function (int $level, string $message): bool {
            $this->warnings[] = $message;

            return true;
        }, E_USER_WARNING);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($GLOBALS['xoopsUser']);
        if (is_dir($this->dir)) {
            Filesystem::deleteDirectory($this->dir);
        }
    }

    public function testADeclaredSampleFolderTheModuleDoesNotShipIsSkippedQuietly(): void
    {
        $GLOBALS['xoopsUser'] = new \XoopsUser(false, ['2', '1']);

        Installer::copyTestFolders(new Configurator($this->dir . '/module'));

        self::assertSame([], $this->warnings);
        self::assertFileExists($this->dir . '/uploads/images/a.txt');
    }

    public function testAFolderThatExistsButIsNotCopiedStillWarns(): void
    {
        // A module-only admin is not a webmaster, so the copy of the existing folder is refused.
        $GLOBALS['xoopsUser'] = new \XoopsUser(true, ['2', '4']);

        Installer::copyTestFolders(new Configurator($this->dir . '/module'));

        self::assertCount(1, $this->warnings);
        self::assertStringContainsString('/module/testdata/images" was not copied', $this->warnings[0]);
        self::assertFileDoesNotExist($this->dir . '/uploads/images/a.txt');
    }
}
