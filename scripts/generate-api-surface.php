<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

// Package-local vendor/ when developed standalone; Core's xoops_lib/vendor/ when installed there.
$autoload = is_file(dirname(__DIR__) . '/vendor/autoload.php')
    ? dirname(__DIR__) . '/vendor/autoload.php'
    : dirname(__DIR__, 3) . '/autoload.php';
require $autoload;

$packageRoot = dirname(__DIR__);
$outputFile  = $packageRoot . '/resources/gate/api-surface.json';
$checkOnly   = in_array('--check', $argv, true);
$parser      = new ParserFactory()->createForNewestSupportedVersion();
$printer     = new Standard();
$finder      = new NodeFinder();
$surface     = [
    'schema_version'  => 1,
    'package'         => 'xoops/moduletools',
    'autoload'        => json_decode((string) file_get_contents($packageRoot . '/composer.json'), true)['autoload'] ?? [],
    'alias_rules'     => [],
    'global_functions' => [],
    'symbols'         => [],
];

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packageRoot . '/src', FilesystemIterator::SKIP_DOTS),
);
foreach ($iterator as $file) {
    if ($file instanceof SplFileInfo && 'php' === $file->getExtension()) {
        $files[] = $file->getPathname();
    }
}
sort($files);

foreach ($files as $file) {
    $source = file_get_contents($file);
    if (!is_string($source)) {
        throw new RuntimeException('Unable to read ' . $file);
    }

    $ast = $parser->parse($source);
    if (null === $ast) {
        continue;
    }

    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver());
    $ast      = $traverser->traverse($ast);
    $relative = str_replace('\\', '/', substr($file, strlen($packageRoot) + 1));

    foreach ($finder->findInstanceOf($ast, Stmt\Function_::class) as $function) {
        if (!$function instanceof Stmt\Function_ || !isset($function->namespacedName)) {
            continue;
        }
        $surface['global_functions'][$function->namespacedName->toString()] = functionRecord($function, $printer);
    }

    foreach (
        $finder->find($ast, static fn (Node $node): bool => $node instanceof Stmt\Class_
        || $node instanceof Stmt\Interface_ || $node instanceof Stmt\Trait_) as $symbol
    ) {
        if (!isset($symbol->namespacedName)) {
            continue;
        }

        $name = $symbol->namespacedName->toString();
        $symbolDoc = $symbol->getDocComment()?->getText() ?? '';
        if (str_contains($name, '\\Internal\\') && str_contains($symbolDoc, '@internal')) {
            continue;
        }
        $kind = $symbol instanceof Stmt\Interface_ ? 'interface'
            : ($symbol instanceof Stmt\Trait_ ? 'trait' : 'class');
        $record = [
            'kind'       => $kind,
            'file'       => $relative,
            'final'      => $symbol instanceof Stmt\Class_ && $symbol->isFinal(),
            'abstract'   => $symbol instanceof Stmt\Class_ && $symbol->isAbstract(),
            'extends'    => [],
            'implements' => [],
            'constants'  => [],
            'properties' => [],
            'methods'    => [],
        ];

        if ($symbol instanceof Stmt\Class_ && null !== $symbol->extends) {
            $record['extends'][] = $symbol->extends->toString();
        } elseif ($symbol instanceof Stmt\Interface_) {
            $record['extends'] = array_map(static fn (Node\Name $n): string => $n->toString(), $symbol->extends);
        }
        if ($symbol instanceof Stmt\Class_) {
            $record['implements'] = array_map(static fn (Node\Name $n): string => $n->toString(), $symbol->implements);
        }

        foreach ($symbol->stmts as $member) {
            if ($member instanceof Stmt\ClassConst && $member->isPublic()) {
                foreach ($member->consts as $constant) {
                    $record['constants'][$constant->name->toString()] = [
                        'type'  => typeString($member->type),
                        'value' => $printer->prettyPrintExpr($constant->value),
                    ];
                }
            } elseif ($member instanceof Stmt\Property && $member->isPublic()) {
                foreach ($member->props as $property) {
                    $record['properties'][$property->name->toString()] = [
                        'static'   => $member->isStatic(),
                        'readonly' => $member->isReadonly(),
                        'type'     => typeString($member->type),
                        'default'  => null === $property->default ? null : $printer->prettyPrintExpr($property->default),
                    ];
                }
            } elseif ($member instanceof Stmt\ClassMethod && $member->isPublic()) {
                $record['methods'][$member->name->toString()] = functionRecord($member, $printer);
                // Promoted constructor parameters are public properties too.
                foreach ($member->params as $parameter) {
                    if ($parameter->isPublic() && $parameter->var instanceof Node\Expr\Variable && is_string($parameter->var->name)) {
                        $record['properties'][$parameter->var->name] = [
                            'static'   => false,
                            'readonly' => $parameter->isReadonly(),
                            'type'     => typeString($parameter->type),
                            'default'  => null === $parameter->default ? null : $printer->prettyPrintExpr($parameter->default),
                        ];
                    }
                }
            }
        }

        ksort($record['constants']);
        ksort($record['properties']);
        ksort($record['methods']);
        $surface['symbols'][$name] = $record;
    }

    if ('src/legacy_aliases.php' === $relative) {
        $surface['alias_rules'][] = [
            'legacy_prefix' => 'XoopsModules\\Mtools\\',
            'modern_prefix' => 'Xoops\\ModuleTools\\',
            'mechanism'     => 'lazy class_alias autoloader',
            'file'          => $relative,
        ];
    }
}

ksort($surface['global_functions']);
ksort($surface['symbols']);
// Always LF: the snapshot is committed, and the --check comparison must not depend on the OS.
$json = json_encode($surface, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";

if ($checkOnly) {
    $existing = is_file($outputFile) ? str_replace("\r\n", "\n", (string) file_get_contents($outputFile)) : false;
    if ($existing !== $json) {
        fwrite(STDERR, "API surface is stale. Run composer api:generate.\n");
        exit(1);
    }
    fwrite(STDOUT, "API surface is current.\n");
    exit(0);
}

file_put_contents($outputFile, $json);
fwrite(STDOUT, sprintf("Wrote %s (%d symbols).\n", $outputFile, count($surface['symbols'])));

/** @return array<string, mixed> */
function functionRecord(Stmt\Function_|Stmt\ClassMethod $function, Standard $printer): array
{
    $parameters = [];
    foreach ($function->params as $parameter) {
        $parameters[] = [
            'name'       => is_string($parameter->var->name) ? $parameter->var->name : '',
            'type'       => typeString($parameter->type),
            'by_ref'     => $parameter->byRef,
            'variadic'   => $parameter->variadic,
            'has_default' => null !== $parameter->default,
            'default'    => null === $parameter->default ? null : $printer->prettyPrintExpr($parameter->default),
        ];
    }

    $doc = $function->getDocComment()?->getText() ?? '';
    preg_match_all('/@throws\s+([^\s*]+)/', $doc, $matches);

    return [
        'static'      => $function instanceof Stmt\ClassMethod && $function->isStatic(),
        'by_ref'      => $function->byRef,
        'parameters'  => $parameters,
        'return_type' => typeString($function->returnType),
        'throws'      => array_values(array_unique($matches[1] ?? [])),
        'documented_false_or_null' => (bool) preg_match('/(?:false|null)/i', $doc),
    ];
}

function typeString(Node\Identifier|Node\Name|Node\ComplexType|null $type): ?string
{
    if (null === $type) {
        return null;
    }
    if ($type instanceof Node\NullableType) {
        return '?' . typeString($type->type);
    }
    if ($type instanceof Node\UnionType) {
        return implode('|', array_map(typeString(...), $type->types));
    }
    if ($type instanceof Node\IntersectionType) {
        return implode('&', array_map(typeString(...), $type->types));
    }

    return $type->toString();
}
