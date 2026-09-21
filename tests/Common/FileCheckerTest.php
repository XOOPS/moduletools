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

    public function testSetFilePermissionsRefusesADirectory(): void
    {
        $base = sys_get_temp_dir() . '/fc-dir-' . uniqid('', true);
        mkdir($base);
        $dir = $base . '/sub';
        mkdir($dir, 0700);

        try {
            self::assertFalse(FileChecker::setFilePermissions($dir, 0775, $base));
            self::assertSame('700', mb_substr(decoct(fileperms($dir)), -3));
        } finally {
            rmdir($dir);
            rmdir($base);
        }
    }
}
