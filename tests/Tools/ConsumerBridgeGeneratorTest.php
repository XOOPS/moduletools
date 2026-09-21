<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Internal\Tools\ConsumerBridgeGenerator;

require_once dirname(__DIR__, 2) . '/src/Internal/Tools/ConsumerBridgeGenerator.php';

final class ConsumerBridgeGeneratorTest extends TestCase
{
    public function testParsesSqlAndSelectsOnlyIntegerSingleKeyTables(): void
    {
        $sql = <<<'SQL'
CREATE TABLE `sample_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL DEFAULT '',
    `body` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `published_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE sample_join (
    left_id INT NOT NULL,
    right_id INT NOT NULL,
    PRIMARY KEY (left_id, right_id)
);

CREATE TABLE sample_meta (
    metakey VARCHAR(64) NOT NULL,
    value TEXT NOT NULL,
    PRIMARY KEY (metakey)
);
SQL;

        $generator = new ConsumerBridgeGenerator();
        $tables = $generator->parseSql($sql);

        self::assertSame(['sample_items', 'sample_join', 'sample_meta'], array_keys($tables));
        self::assertSame(['id', 'title', 'body', 'price', 'published_at'], array_keys($tables['sample_items']['columns']));
        self::assertSame(['id'], $tables['sample_items']['primaryKey']);
        self::assertTrue($tables['sample_items']['columns']['id']['autoIncrement']);
        self::assertSame('float', $tables['sample_items']['columns']['price']['phpType']);
        self::assertTrue($tables['sample_items']['columns']['published_at']['nullable']);

        $eligible = $generator->eligibleTables($tables);

        self::assertSame(['sample_items'], array_keys($eligible));
        self::assertSame('composite-primary-key', $tables['sample_join']['skipReason']);
        self::assertSame('non-integer-primary-key', $tables['sample_meta']['skipReason']);
    }

    public function testGeneratesAnExactRoundTripManifest(): void
    {
        $sql = <<<'SQL'
CREATE TABLE sample_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    published_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
);
SQL;

        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'moduletools-bridge-generator-' . bin2hex(random_bytes(6));
        mkdir($root, 0777, true);

        try {
            $generator = new ConsumerBridgeGenerator();
            $result = $generator->generate(
                modulePath: $root,
                moduleDirname: 'sample',
                moduleNamespace: 'Sample',
                tables: $generator->eligibleTables($generator->parseSql($sql)),
                allTables: $generator->parseSql($sql),
            );

            self::assertSame(1, $result['generatedTables']);
            self::assertFileExists($root . '/class/Modern/SampleItemsEntity.php');
            self::assertFileExists($root . '/class/Modern/SampleItemsSchema.php');
            self::assertFileExists($root . '/class/Modern/SampleItemsRepository.php');
            self::assertFileExists($root . '/class/Modern/BridgedSampleItemsHandler.php');
            self::assertFileExists($root . '/tests/run-bridge-smoke.php');
            self::assertFileExists($root . '/docs/architecture/xoops4-bridge.md');

            $entity = file_get_contents($root . '/class/Modern/SampleItemsEntity.php');
            self::assertIsString($entity);
            self::assertStringContainsString("'title' => \$this->title", $entity);
            self::assertStringContainsString("'body' => \$this->body", $entity);

            $repository = file_get_contents($root . '/class/Modern/SqlBridgeRepository.php');
            self::assertIsString($repository);
            self::assertStringContainsString("private const OPERATORS = ['=',", $repository);
            self::assertStringContainsString("in_array(\$connector, ['AND', 'OR'], true)", $repository);
            self::assertStringContainsString('if ($assignments === [])', $repository);

            $manifest = file_get_contents($root . '/class/Modern/BridgeManifest.php');
            self::assertIsString($manifest);
            self::assertStringContainsString("'rows' =>", $manifest);
            self::assertStringNotContainsString("'row' =>", $manifest);
        } finally {
            if (is_dir($root)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST,
                );
                foreach ($iterator as $entry) {
                    $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
                }
                rmdir($root);
            }
        }
    }
}
