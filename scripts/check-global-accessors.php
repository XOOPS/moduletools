<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\ParserFactory;

// Package-local vendor/ when developed standalone; Core's xoops_lib/vendor/ when installed there.
$autoload = is_file(dirname(__DIR__) . '/vendor/autoload.php')
    ? dirname(__DIR__) . '/vendor/autoload.php'
    : dirname(__DIR__, 3) . '/autoload.php';
require $autoload;

$packageRoot = dirname(__DIR__);
$parser = new ParserFactory()->createForNewestSupportedVersion();
$errors = [];
$count = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packageRoot . '/src', FilesystemIterator::SKIP_DOTS),
);
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
        continue;
    }
    $ast = $parser->parse((string) file_get_contents($file->getPathname())) ?? [];
    foreach ($ast as $node) {
        inspectGlobals($node, '', $file->getPathname(), $errors, $count);
    }
}

if ([] !== $errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, sprintf("Legacy-global boundary valid (%d accessor reads/writes).\n", $count));

/** @param list<string> $errors */
function inspectGlobals(Node $node, string $methodDoc, string $file, array &$errors, int &$count): void
{
    if ($node instanceof Node\Stmt\ClassMethod) {
        $methodDoc = $node->getDocComment()?->getText() ?? '';
    }
    if ($node instanceof Node\Expr\ArrayDimFetch
        && $node->var instanceof Node\Expr\Variable
        && 'GLOBALS' === $node->var->name) {
        ++$count;
        if (!str_contains($methodDoc, '@legacy-global-accessor')) {
            $errors[] = $file . ':' . $node->getStartLine() . ' $GLOBALS access is outside a designated accessor';
        }
    }
    foreach ($node->getSubNodeNames() as $subNodeName) {
        $child = $node->{$subNodeName};
        if ($child instanceof Node) {
            inspectGlobals($child, $methodDoc, $file, $errors, $count);
        } elseif (is_array($child)) {
            foreach ($child as $item) {
                if ($item instanceof Node) {
                    inspectGlobals($item, $methodDoc, $file, $errors, $count);
                }
            }
        }
    }
}
