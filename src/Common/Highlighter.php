<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Common;

/** Highlights user-supplied search terms in text while escaping the input. */
final readonly class Highlighter
{
    /** @var list<string> */
    private array $terms;

    public function __construct(string $keywords, bool $singleWords = true, private string $cssClass = 'moduletools-highlight')
    {
        $terms = $singleWords ? preg_split('/\s+/u', trim($keywords)) : [trim($keywords)];
        $this->terms = array_values(array_filter(array_unique(array_map(strval(...), $terms ?: [])), static fn (string $term): bool => '' !== $term));
    }

    public function highlight(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        if ([] === $this->terms) {
            return $escaped;
        }
        $pattern = '/(' . implode('|', array_map(static fn (string $term): string => preg_quote($term, '/'), $this->terms)) . ')/iu';
        return preg_replace($pattern, '<mark class="' . htmlspecialchars($this->cssClass, ENT_QUOTES | ENT_HTML5) . '">$1</mark>', $escaped) ?? $escaped;
    }
}
