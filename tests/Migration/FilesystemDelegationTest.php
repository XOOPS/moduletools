<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Xoops\Helpers\Utility\Filesystem;
use Xoops\ModuleTools\Common\DirectoryChecker;
use Xoops\ModuleTools\Common\FileChecker;

final class FilesystemDelegationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/moduletools-' . bin2hex(random_bytes(8));
        self::assertTrue(Filesystem::mkdir($this->temporaryDirectory));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->temporaryDirectory)) {
            Filesystem::deleteDirectory($this->temporaryDirectory);
        }
    }

    public function testDirectoryCreationMatchesHelpersFilesystem(): void
    {
        $viaShim   = $this->temporaryDirectory . '/shim';
        $viaTarget = $this->temporaryDirectory . '/target';

        self::assertSame(
            Filesystem::mkdir($viaTarget, 0755, false),
            DirectoryChecker::createDirectory($viaShim, 0755, $this->temporaryDirectory),
        );
        self::assertTrue(is_dir($viaShim));
        self::assertTrue(is_dir($viaTarget));
    }

    public function testFileCopyMatchesHelpersFilesystem(): void
    {
        $source    = $this->temporaryDirectory . '/source.txt';
        $viaShim   = $this->temporaryDirectory . '/shim.txt';
        $viaTarget = $this->temporaryDirectory . '/target.txt';
        file_put_contents($source, 'moduletools parity');

        self::assertSame(
            Filesystem::copy($source, $viaTarget),
            FileChecker::copyFile($source, $viaShim, $this->temporaryDirectory),
        );
        self::assertSame(file_get_contents($viaTarget), file_get_contents($viaShim));
    }
}
