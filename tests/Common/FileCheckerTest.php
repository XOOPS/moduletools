<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\FileChecker;

#[CoversClass(FileChecker::class)]
final class FileCheckerTest extends TestCase
{
    public function testExplicitBaseRejectsTraversalInANonExistentTail(): void
    {
        $base = sys_get_temp_dir() . '/fc-base-' . uniqid('', true);
        mkdir($base);
        $source = $base . '/source.txt';
        file_put_contents($source, 'x');
        $outside = $base . '/new/../../fc-outside-' . uniqid('', true) . '.txt';

        try {
            self::assertFalse(FileChecker::copyFile($source, $outside, $base));
            self::assertFileDoesNotExist($outside);
            self::assertFalse(FileChecker::setFilePermissions($outside, 0644, $base));
            self::assertTrue(FileChecker::copyFile($source, $base . '/copy.txt', $base));
        } finally {
            @unlink($base . '/copy.txt');
            unlink($source);
            rmdir($base);
        }
    }

    public function testADanglingDestinationLinkIsNotApprovedThroughItsParent(): void
    {
        $base    = sys_get_temp_dir() . '/fc-link-' . uniqid('', true);
        $outside = sys_get_temp_dir() . '/fc-link-target-' . uniqid('', true) . '.txt';
        mkdir($base);
        file_put_contents($base . '/source.txt', 'x');
        if (!@symlink($outside, $base . '/dangling.txt')) {
            unlink($base . '/source.txt');
            rmdir($base);
            self::markTestSkipped('symlink() is not permitted here');
        }

        try {
            self::assertFalse(FileChecker::copyFile($base . '/source.txt', $base . '/dangling.txt', $base));
            self::assertFileDoesNotExist($outside);
        } finally {
            @unlink($outside);
            unlink($base . '/dangling.txt');
            unlink($base . '/source.txt');
            rmdir($base);
        }
    }

    public function testSetFilePermissionsRefusesADirectory(): void
    {
        $base = sys_get_temp_dir() . '/fc-dir-' . uniqid('', true);
        mkdir($base);
        $dir = $base . '/sub';
        mkdir($dir, 0700);
        clearstatcache(true, $dir);
        $permissions = fileperms($dir);

        try {
            self::assertFalse(FileChecker::setFilePermissions($dir, 0775, $base));
            clearstatcache(true, $dir);
            self::assertSame($permissions, fileperms($dir));
        } finally {
            rmdir($dir);
            rmdir($base);
        }
    }
}
