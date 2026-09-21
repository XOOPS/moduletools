<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Tools;

use PHPUnit\Framework\TestCase;

final class ApiCompatibilityGateTest extends TestCase
{
    private string $root;
    private string $gate;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/moduletools-gate-' . bin2hex(random_bytes(8));
        $this->gate = $this->root . '/resources/gate';
        mkdir($this->gate, 0700, true);
        mkdir($this->root . '/scripts');
        copy(dirname(__DIR__, 2) . '/scripts/check-api-compat.php', $this->root . '/scripts/check-api-compat.php');
        $this->write('api-surface-baseline.json', ['symbols' => [], 'global_functions' => [], 'alias_rules' => []]);
        $this->write('api-surface.json', ['symbols' => ['Example' => []], 'global_functions' => [], 'alias_rules' => []]);
        $this->write('api-surface-allowlist.json', ['allowed_changes' => ['symbols/Example:added']]);
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testRemovedAdditionAndUnexpectedChangeAreBothReported(): void
    {
        self::assertSame(0, $this->runGate()[0]);
        $this->write('api-surface.json', ['symbols' => ['Other' => []], 'global_functions' => [], 'alias_rules' => []]);
        [$status, $output] = $this->runGate();
        self::assertSame(1, $status);
        self::assertStringContainsString('symbols/Example:added', $output);
        self::assertStringContainsString('symbols/Other:added', $output);
    }

    public function testAcceptBaselineClearsApprovalsAndStillPasses(): void
    {
        self::assertSame(0, $this->runGate('--accept-baseline')[0]);
        self::assertSame(['allowed_changes' => []], json_decode(file_get_contents($this->gate . '/api-surface-allowlist.json'), true));
        self::assertSame(0, $this->runGate()[0]);
    }

    public function testFailedBaselineWritePreservesApprovals(): void
    {
        unlink($this->gate . '/api-surface-baseline.json');
        mkdir($this->gate . '/api-surface-baseline.json');
        $before = file_get_contents($this->gate . '/api-surface-allowlist.json');
        self::assertSame(1, $this->runGate('--accept-baseline')[0]);
        self::assertSame($before, file_get_contents($this->gate . '/api-surface-allowlist.json'));
    }

    public function testFailedAllowlistWriteReturnsFailure(): void
    {
        unlink($this->gate . '/api-surface-allowlist.json');
        mkdir($this->gate . '/api-surface-allowlist.json');
        [$status, $output] = $this->runGate('--accept-baseline');
        self::assertSame(1, $status);
        self::assertStringContainsString('clearing the allowlist failed', $output);
    }

    private function write(string $name, array $value): void
    {
        file_put_contents($this->gate . '/' . $name, json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function runGate(string $argument = ''): array
    {
        $process = proc_open([PHP_BINARY, $this->root . '/scripts/check-api-compat.php', $argument], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }
}
