<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests;

use PHPUnit\Framework\TestCase;

final class ErrorLevelTest extends TestCase
{
    /**
     * Passing E_USER_ERROR to trigger_error() is deprecated as of PHP 8.4: a failure
     * that must stop the request is thrown instead.
     */
    public function testNoSourceFileRaisesAUserError(): void
    {
        $root = dirname(__DIR__) . '/src';
        $hits = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            foreach (self::userErrorLines((string) file_get_contents($file->getPathname())) as $line) {
                $hits[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)) . ':' . $line;
            }
        }

        sort($hits);
        self::assertSame([], $hits);
    }

    public function testTheScanReadsCallsAndNotText(): void
    {
        $source = <<<'PHP'
            <?php
            trigger_error('plain', E_USER_ERROR);
            \TRIGGER_ERROR('upper case; with a semicolon', \E_USER_ERROR);
            trigger_error(
                sprintf('spread over %s', 'lines'),
                E_USER_ERROR
            );
            trigger_error('E_USER_ERROR is only text here', E_USER_WARNING);
            // trigger_error('in a comment', E_USER_ERROR);
            throw new \RuntimeException('thrown', E_USER_ERROR);
            PHP;

        self::assertSame([2, 3, 4], self::userErrorLines($source));
    }

    /**
     * Lines of the trigger_error() calls in $source that pass the E_USER_ERROR constant.
     *
     * @return list<int>
     */
    private static function userErrorLines(string $source): array
    {
        $tokens = \PhpToken::tokenize($source);
        $names  = [T_STRING, T_NAME_FULLY_QUALIFIED];
        $lines  = [];

        foreach ($tokens as $i => $token) {
            if (!$token->is($names) || 'trigger_error' !== strtolower(ltrim($token->text, '\\'))) {
                continue;
            }
            // Walk the argument list of this call to its closing parenthesis.
            $depth = 0;
            for ($j = $i + 1, $count = count($tokens); $j < $count; $j++) {
                $text = $tokens[$j]->text;
                if ('(' === $text) {
                    $depth++;
                } elseif (')' === $text && 0 === --$depth) {
                    break;
                } elseif (0 === $depth && !$tokens[$j]->isIgnorable()) {
                    break; // not a call: no parenthesis follows the name
                } elseif ($tokens[$j]->is($names) && 'E_USER_ERROR' === ltrim($text, '\\')) {
                    $lines[] = $token->line;
                    break;
                }
            }
        }

        return $lines;
    }
}
