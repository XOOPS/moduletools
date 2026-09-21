<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

// Package-local vendor/ when developed standalone; Core's xoops_lib/vendor/ when installed there.
$autoload = is_file(dirname(__DIR__) . '/vendor/autoload.php')
    ? dirname(__DIR__) . '/vendor/autoload.php'
    : dirname(__DIR__, 3) . '/autoload.php';
require $autoload;

$packageRoot = dirname(__DIR__);
$matrix = json_decode((string) file_get_contents($packageRoot . '/resources/gate/destination-matrix.json'), true, flags: JSON_THROW_ON_ERROR);
$outputCompatibility = [];
foreach ($matrix['records'] as $record) {
    if ('type' === $record['kind'] && ('retain-compat' === $record['disposition']
        || 'blocked-on-upstream' === $record['disposition']
        || 'migrate-on-4.0' === $record['availability'])) {
        $outputCompatibility[$record['symbol']] = true;
    }
}

$parser = new ParserFactory()->createForNewestSupportedVersion();
$finder = new NodeFinder();
$errors = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packageRoot . '/src', FilesystemIterator::SKIP_DOTS),
);
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
        continue;
    }
    $source = (string) file_get_contents($file->getPathname());
    $ast = $parser->parse($source) ?? [];
    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver());
    $ast = $traverser->traverse($ast);
    $classes = $finder->find($ast, static fn (Node $node): bool => $node instanceof Node\Stmt\Class_
        || $node instanceof Node\Stmt\Interface_ || $node instanceof Node\Stmt\Trait_);
    foreach ($classes as $class) {
        if (!isset($class->namespacedName)) {
            continue;
        }
        $name = $class->namespacedName->toString();
        $doc  = $class->getDocComment()?->getText() ?? '';
        if (str_contains($name, '\\Internal\\') && !str_contains($doc, '@internal')) {
            $errors[] = $name . ': Internal class is missing @internal';
        }

        $allowsOutput = isset($outputCompatibility[$name]);
        $outputNodes = $finder->find($class->stmts, static function (Node $node): bool {
            if ($node instanceof Node\Stmt\Echo_) {
                return true;
            }
            if (!$node instanceof Node\Expr\FuncCall || !$node->name instanceof Node\Name) {
                return false;
            }
            return in_array(strtolower($node->name->toString()), ['redirect_header', 'header'], true);
        });
        if (!$allowsOutput && [] !== $outputNodes) {
            $errors[] = $name . ': transport output outside retain-compat';
        }
    }
}

if ([] !== $errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, "Layer and output boundaries are valid.\n");
