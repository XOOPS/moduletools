<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Paginator;

#[CoversClass(Paginator::class)]
final class PaginatorTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING'], $_GET['g2p']);
    }

    #[Test]
    public function pageLinksEscapeThePathAndSuffixWithoutDoubleEncodingTheQuery(): void
    {
        $_SERVER['PHP_SELF']     = '/modules/quotes/index.php"><script>x</script>';
        $_SERVER['QUERY_STRING'] = 'cat=3&q=a%26b';
        $_GET['g2p']             = '1';

        $paginator = new Paginator(50, 10, 5);
        $paginator->setUrlOther("#top' onmouseover='x");
        $paginator->init();

        $center = $paginator->makeBootStrapBar()['center'];

        self::assertStringNotContainsString('"><script>', $center);
        self::assertStringNotContainsString("' onmouseover", $center);
        self::assertStringContainsString('href="/modules/quotes/index.php&quot;&gt;&lt;script&gt;x&lt;/script&gt;?cat=3&amp;q=a&amp;b&amp;g2p=2#top&#039; onmouseover=&#039;x"', $center);
    }
}
