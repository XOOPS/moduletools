<?php

declare(strict_types=1);

namespace {
    if (!defined('XOOPS_ROOT_PATH')) {
        define('XOOPS_ROOT_PATH', 'C:/test&root');
    }

    if (!function_exists('xoops_loadLanguage')) {
        function xoops_loadLanguage(string $name, string $module): bool
        {
            unset($name, $module);

            return true;
        }
    }

    $labels = [
        'GDLIBSTATUS'       => 'GD: ',
        'GDLIBVERSION'      => 'GD version: ',
        'GDOFF'             => 'off',
        'GDON'              => 'on',
        'IMAGEINFO'         => 'quotes-status',
        'MAXPOSTSIZE'       => 'post: ',
        'MAXUPLOADSIZE'     => 'upload: ',
        'MEMORYLIMIT'       => 'memory: ',
        'OFF'               => 'off',
        'ON'                => 'on',
        'SERVERPATH'        => 'root: ',
        'SERVERUPLOADSTATUS' => 'uploads: ',
        'SPHPINI'           => 'php.ini',
        'UPLOADPATHDSC'     => 'upload note',
    ];

    foreach ($labels as $suffix => $label) {
        defined('_CO_QUOTES_' . $suffix) || define('_CO_QUOTES_' . $suffix, $label);
        defined('_CO_MTOOLS_' . $suffix) || define('_CO_MTOOLS_' . $suffix, 'mtools-' . $label);
    }
}

namespace XoopsModules\Quotes {
    final class ServerStatsFixture
    {
        use \Xoops\ModuleTools\Common\ServerStats;
    }
}

namespace Xoops\ModuleTools\Tests\Common {
    use PHPUnit\Framework\TestCase;
    use XoopsModules\Quotes\ServerStatsFixture;

    final class ServerStatsTest extends TestCase
    {
        public function testUsesConsumerLabelsAndEscapesTheServerPath(): void
        {
            $html = ServerStatsFixture::getServerStats();

            self::assertStringContainsString('quotes-status', $html);
            self::assertStringNotContainsString('mtools-quotes-status', $html);
            self::assertStringContainsString('C:/test&amp;root', $html);
            self::assertStringNotContainsString('C:/test&root', $html);
        }
    }
}
