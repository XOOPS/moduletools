<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Cloner;

#[CoversClass(Cloner::class)]
final class ClonerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/moduletools-cloner-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/quotes/class', 0777, true);
        mkdir($this->root . '/quotes/node_modules');
        file_put_contents($this->root . '/quotes/class/QuotesHandler.php', "<?php\nnamespace XoopsModules\\Quotes;\ndefine('QUOTES_URL', 'quotes');\n");
        file_put_contents($this->root . '/quotes/logo.png', "\x89PNG quotes Quotes QUOTES");
        file_put_contents($this->root . '/quotes/node_modules/x.js', 'quotes');
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->root);
    }

    #[Test]
    public function cloneRewritesNamesInTextFilesAndPathsButNotBinaries(): void
    {
        $target = Cloner::clone($this->root . '/quotes', 'Sayings');

        self::assertSame($this->root . '/sayings', $target);
        self::assertFileExists($target . '/class/SayingsHandler.php');
        self::assertStringEqualsFile($target . '/class/SayingsHandler.php', "<?php\nnamespace XoopsModules\\Sayings;\ndefine('SAYINGS_URL', 'sayings');\n");
        self::assertStringEqualsFile($target . '/logo.png', "\x89PNG quotes Quotes QUOTES");
        self::assertDirectoryDoesNotExist($target . '/node_modules');
    }

    #[Test]
    public function cloneRejectsBadNamesAndExistingTargets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cloner::clone($this->root . '/quotes', '../evil');
    }

    #[Test]
    public function cloneRefusesToOverwrite(): void
    {
        mkdir($this->root . '/taken');
        $this->expectException(\InvalidArgumentException::class);
        Cloner::clone($this->root . '/quotes', 'taken');
    }

    #[Test]
    public function createLogoFailsGracefullyWithoutAssets(): void
    {
        self::assertFalse(Cloner::createLogo($this->root . '/quotes'));
    }

    private function rmdir(string $dir): void
    {
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
