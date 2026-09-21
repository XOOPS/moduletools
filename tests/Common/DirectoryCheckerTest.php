<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\DirectoryChecker;

#[CoversClass(DirectoryChecker::class)]
final class DirectoryCheckerTest extends TestCase
{
    public function testMissingDirectoryOffersACreateButton(): void
    {
        $missing = $this->uploadPath() . '/dc-missing';
        @rmdir($missing);

        $html = (string) DirectoryChecker::getDirectoryStatus($missing, 0755, '/admin/index.php');

        self::assertStringContainsString('Not available', $html);
        self::assertStringContainsString("value='mtools_createdir'", $html);
        self::assertStringContainsString('Create it', $html);
        self::assertStringContainsString("value='755'", $html);
    }

    public function testExistingDirectoryReportsAvailableWithoutACreateButton(): void
    {
        $existing = $this->uploadPath() . '/dc-existing';
        self::assertTrue(DirectoryChecker::createDirectory($existing, 0755));

        $html = (string) DirectoryChecker::getDirectoryStatus($existing, 0755, '/admin/index.php');

        self::assertStringNotContainsString('mtools_createdir', $html);
        rmdir($existing);
    }

    public function testFormActionAcceptsOnlyLocalPaths(): void
    {
        $missing = $this->uploadPath() . '/dc-missing';
        @rmdir($missing);
        $_SERVER['SCRIPT_NAME'] = '/admin/fallback.php';

        try {
            foreach (['javascript:alert(1)', ' javascript:alert(1)', 'data:text/html,x', 'https://evil.test/', '//evil.test/x', ' //evil.test/x', "\t/admin/x.php", '/admin/x\\y.php', ''] as $bad) {
                $html = (string) DirectoryChecker::getDirectoryStatus($missing, 0755, $bad);
                self::assertStringContainsString("<form action='/admin/fallback.php'", $html, 'rejected: ' . $bad);
            }
            $html = (string) DirectoryChecker::getDirectoryStatus($missing, 0755, '/admin/index.php?op=list');
            self::assertStringContainsString("<form action='/admin/index.php?op=list'", $html);
        } finally {
            unset($_SERVER['SCRIPT_NAME']);
        }
    }

    public function testHandleRequestFailsClosedWithoutASecurityService(): void
    {
        $target = $this->uploadPath() . '/dc-csrf-' . uniqid('', true);
        $_POST  = ['op' => 'mtools_createdir', 'path' => $target, 'mode' => '0755'];
        unset($GLOBALS['xoopsSecurity']);
        $GLOBALS['xoopsUser'] = new \XoopsUser(true);

        try {
            DirectoryChecker::handleRequest('/admin/index.php');
            self::fail('handleRequest() must redirect when no security service is available');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('Security token', $e->getMessage());
        } finally {
            $_POST = [];
            unset($GLOBALS['xoopsUser']);
        }

        self::assertDirectoryDoesNotExist($target);
    }

    public function testHandleRequestRefusesANonAdministratorBeforeLookingAtTheToken(): void
    {
        $target = $this->uploadPath() . '/dc-user-' . uniqid('', true);
        $_POST  = ['op' => 'mtools_createdir', 'path' => $target, 'mode' => '0755'];
        unset($GLOBALS['xoopsSecurity']);

        foreach ([null, new \XoopsUser(false)] as $user) {
            $GLOBALS['xoopsUser'] = $user;
            try {
                DirectoryChecker::handleRequest('/admin/index.php');
                self::fail('handleRequest() must redirect a non-administrator');
            } catch (\RuntimeException $e) {
                self::assertStringContainsString('Permission denied', $e->getMessage());
            }
        }
        $_POST = [];
        unset($GLOBALS['xoopsUser']);

        self::assertDirectoryDoesNotExist($target);
    }

    public function testExplicitBaseRejectsTraversalInANonExistentTail(): void
    {
        $base = sys_get_temp_dir() . '/dc-base-' . uniqid('', true);
        mkdir($base);
        $outside = $base . '/new/../../dc-outside-' . uniqid('', true);

        try {
            self::assertFalse(DirectoryChecker::createDirectory($outside, 0755, $base));
            self::assertFalse(DirectoryChecker::setDirectoryPermissions($outside, 0755, $base));
            self::assertDirectoryDoesNotExist($outside);
            self::assertTrue(DirectoryChecker::createDirectory($base . '/new/child', 0755, $base));
        } finally {
            @rmdir($base . '/new/child');
            @rmdir($base . '/new');
            @rmdir($base);
        }
    }

    public function testSetDirectoryPermissionsRefusesAFile(): void
    {
        $base = sys_get_temp_dir() . '/dc-file-' . uniqid('', true);
        mkdir($base);
        $file = $base . '/plain.txt';
        file_put_contents($file, 'x');
        chmod($file, 0600);

        try {
            self::assertFalse(DirectoryChecker::setDirectoryPermissions($file, 0775, $base));
            self::assertSame('600', mb_substr(decoct(fileperms($file)), -3));
        } finally {
            unlink($file);
            rmdir($base);
        }
    }

    private function uploadPath(): string
    {
        if (!defined('XOOPS_URL')) {
            define('XOOPS_URL', 'http://localhost');
        }
        if (!defined('XOOPS_ROOT_PATH')) {
            define('XOOPS_ROOT_PATH', sys_get_temp_dir());
        }
        if (!defined('XOOPS_UPLOAD_PATH')) {
            define('XOOPS_UPLOAD_PATH', sys_get_temp_dir());
        }

        return XOOPS_UPLOAD_PATH;
    }
}
