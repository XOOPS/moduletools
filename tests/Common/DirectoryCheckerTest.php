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

    public function testHandleRequestFailsClosedWithoutASecurityService(): void
    {
        $target = $this->uploadPath() . '/dc-csrf-' . uniqid('', true);
        $_POST  = ['op' => 'mtools_createdir', 'path' => $target, 'mode' => '0755'];
        unset($GLOBALS['xoopsSecurity']);

        try {
            DirectoryChecker::handleRequest('/admin/index.php');
            self::fail('handleRequest() must redirect when no security service is available');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('Security token', $e->getMessage());
        } finally {
            $_POST = [];
        }

        self::assertDirectoryDoesNotExist($target);
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
