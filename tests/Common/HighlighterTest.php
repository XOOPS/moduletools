<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Highlighter;

#[CoversClass(Highlighter::class)]
final class HighlighterTest extends TestCase
{
    #[Test]
    public function marksTermsInsideEscapedTextIncludingEntities(): void
    {
        $highlighter = new Highlighter('R&D "plan"', false);

        self::assertSame(
            'Our <mark class="moduletools-highlight">R&amp;D &quot;plan&quot;</mark> for &lt;b&gt;',
            $highlighter->highlight('Our R&D "plan" for <b>'),
        );
    }

    #[Test]
    public function termsNeverMatchInsideEntitiesTheEscapingProduced(): void
    {
        self::assertSame('a &amp; b', new Highlighter('amp')->highlight('a & b'));
        self::assertSame('&quot;x&quot;', new Highlighter('quot')->highlight('"x"'));
        self::assertSame('<mark class="moduletools-highlight">amp</mark> &amp; &lt;', new Highlighter('amp')->highlight('amp & <'));
    }

    #[Test]
    public function longerTermsWinOverTheirOwnPrefixes(): void
    {
        self::assertSame('<mark class="moduletools-highlight">abc</mark>', (new Highlighter('ab abc'))->highlight('abc'));
    }

    #[Test]
    public function aBareAmpersandTermMatchesTheWholeEntity(): void
    {
        $highlighter = new Highlighter('&');

        self::assertSame('a <mark class="moduletools-highlight">&amp;</mark> b', $highlighter->highlight('a & b'));
    }
}
