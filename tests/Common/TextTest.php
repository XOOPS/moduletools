<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Common;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Common\Text;

final class TextTest extends TestCase
{
    public function testShortHtmlEntitiesRemainUnchanged(): void
    {
        foreach (['&amp;', '&#38;', '&#x26;', '<b>&amp;</b>', 'é'] as $text) {
            self::assertSame($text, Text::truncateHtml($text, 1, '...', true));
        }
    }

    public function testShortLimitsNeverUseNegativeContentBudgets(): void
    {
        foreach ([true, false] as $html) {
            self::assertSame('.', Text::truncateHtml('abcdef', 1, '...', true, $html));
            self::assertSame('..', Text::truncateHtml('abcdef', 2, '...', false, $html));
            self::assertSame('', Text::truncateHtml('abcdef', 0, '...', true, $html));
            self::assertSame('', Text::truncateHtml('abcdef', -1, '...', true, $html));
            self::assertSame('é', Text::truncateHtml('abcdef', 1, 'éé', true, $html));
        }
        self::assertSame('.', Text::truncateHtml('<b>&amp;abc</b>', 1, '...', true));
    }

    public function testExactTruncationCountsEntitiesAndMultibyteCharacters(): void
    {
        foreach (['face; more' => 'face', '&amp;abcdef' => '&amp;abc', '&#38;abcdef' => '&#38;abc', '&#x26;abcdef' => '&#x26;abc', 'éé&amp;abcdef' => 'éé&amp;a'] as $input => $expected) {
            self::assertSame($expected, Text::truncateHtml($input, 4, '', true));
        }
    }

    public function testClosingTagsMatchCaseInsensitivelyAndCloseInnermostFirst(): void
    {
        // "</B>" used to miss the lowercased "b" on the stack and get a second "</b>" appended.
        self::assertSame('<B>ab</B><i>cd</i>', Text::truncateHtml('<B>ab</B><i>cdefgh</i>', 4, '', true));
        self::assertSame('<b><i>abc</i></b>', Text::truncateHtml('<b><i>abcdef</i></b>', 3, '', true));
        self::assertSame('<b><i>ab</i>c</b>', Text::truncateHtml('<b><i>ab</i>cdef</b>', 3, '', true));
    }

    public function testATagFloodFallsBackToAPlainTextExcerpt(): void
    {
        // Tags cost no visible characters; this input made the old array_unshift walk quadratic.
        $flood = str_repeat('<a>', 20_000) . 'abcdef' . str_repeat('</b>', 20_000);

        self::assertSame('abc', Text::truncateHtml($flood, 3, '', true));
    }

    public function testTheTagFloodFallbackKeepsParagraphsApartAndEntitiesWhole(): void
    {
        $flood = str_repeat('<b></b>', 2_000);

        self::assertSame('Hello wo', Text::truncateHtml('<p>Hello</p><p>world again</p>' . $flood, 8, '', true));
        // A ">" inside a quoted attribute does not end the tag, or its tail would leak into the text.
        self::assertSame('Hello wo', Text::truncateHtml('<p title="a>b">Hello</p><div data-x=\'c>d\'>world again</div>' . $flood, 8, '', true));
        // A long attribute must not exhaust the PCRE stack (the excerpt came back empty).
        self::assertSame('Hello wo', Text::truncateHtml('<p data-x=' . str_repeat('a', 10_000) . '>Hello</p><div>world again</div>' . $flood, 8, '', true));
        self::assertSame('a &amp; b', Text::truncateHtml('a &amp; b &lt;c&gt; d' . $flood, 5, '', true));
        self::assertSame('&lt;c&gt;', Text::truncateHtml('&lt;c&gt; and more' . $flood, 3, '', true));
        // Malformed UTF-8 used to make the /u whitespace pass return null: an empty excerpt.
        self::assertSame('ab?cd', Text::truncateHtml("ab\xFFcd efgh" . $flood, 5, '', true));
        // The ending is caller markup on both paths: appended once, never re-escaped.
        self::assertSame('a &amp;<i>…</i>', Text::truncateHtml('a &amp; bcdefghijk' . $flood, 11, '<i>…</i>', true));
    }
}
