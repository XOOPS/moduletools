<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Common {
    function curl_setopt($handle, $option, $value): bool
    {
        if (CURLOPT_URL === $option) {
            \PHPUnit\Framework\Assert::assertSame('https://api.github.com/repos/example/module/releases/latest', $value);
        }
        return true;
    }

    function curl_exec($handle): string
    {
        return \Xoops\ModuleTools\Tests\Common\UpdateCheckerTest::$response;
    }
}

namespace Xoops\ModuleTools\Tests\Common {
    use PHPUnit\Framework\TestCase;
    use Xoops\Helpers\Contracts\CacheInterface;
    use Xoops\Helpers\Service\Cache;
    use Xoops\ModuleTools\Common\UpdateChecker;

    final class UpdateCheckerTest extends TestCase
    {
        public static string $response = '';

        public function testLatestStableResponseAndMissingRelease(): void
        {
            if (!extension_loaded('curl')) {
                self::markTestSkipped('cURL is required for update checks.');
            }
            $cache = $this->createMock(CacheInterface::class);
            $cache->method('get')->willReturn(null);
            $cache->method('has')->willReturn(false);
            Cache::use($cache);
            $helper = new class extends \Xmf\Module\Helper {
                public function __construct() {}
                public function getModule() { return false; }
            };
            try {
                self::$response = '{"tag_name":"v1.1.0","prerelease":false,"draft":false}';
                $update = UpdateChecker::checkVerModule($helper, repository: 'example/module');
                self::assertNotNull($update);
                self::assertStringEndsWith('v1.1.0', $update[0]);
                self::assertSame('https://github.com/example/module/archive/v1.1.0.zip', $update[1]);
                foreach (['{"message":"Not Found"}', '{"tag_name":"v2.0.0","prerelease":true}', '{"tag_name":"v2.0.0","draft":true}'] as $response) {
                    self::$response = $response;
                    self::assertNull(UpdateChecker::checkVerModule($helper, repository: 'example/module'));
                }
            } finally {
                Cache::reset();
            }
        }
    }
}
