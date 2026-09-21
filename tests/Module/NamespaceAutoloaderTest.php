<?php declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Module;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Module\NamespaceAutoloader;

final class NamespaceAutoloaderTest extends TestCase
{
    public function testLoadsPsr4AndLegacyFilenamesWithoutSharingModuleState(): void
    {
        $root = sys_get_temp_dir() . '/moduletools-autoload-' . bin2hex(random_bytes(6));
        mkdir($root . '/One', 0777, true);
        mkdir($root . '/Two', 0777, true);
        file_put_contents($root . '/One/Exact.php', "<?php namespace Fixture\\One; final class Exact {}\n");
        file_put_contents($root . '/One/legacy.filename.php', "<?php namespace Fixture\\One; final class LegacyName {}\n");
        file_put_contents($root . '/Two/other.php', "<?php namespace Fixture\\Two; final class LegacyName {}\n");

        NamespaceAutoloader::register('Fixture\\One', $root . '/One');
        NamespaceAutoloader::register('Fixture\\Two\\', $root . '/Two');

        self::assertTrue(class_exists('Fixture\\One\\Exact'));
        self::assertTrue(class_exists('Fixture\\One\\LegacyName'));
        self::assertTrue(class_exists('Fixture\\Two\\LegacyName'));
        self::assertFalse(class_exists('Fixture\\OneMore\\Exact'));

        unlink($root . '/One/Exact.php');
        unlink($root . '/One/legacy.filename.php');
        unlink($root . '/Two/other.php');
        rmdir($root . '/One');
        rmdir($root . '/Two');
        rmdir($root);
    }
}
