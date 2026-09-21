<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$paths       = ['src', 'tests', 'scripts'];
$failures    = [];

foreach ($paths as $relativePath) {
    $path = $packageRoot . DIRECTORY_SEPARATOR . $relativePath;
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
            continue;
        }

        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';
        exec($command, $output, $exitCode);
        if (0 !== $exitCode) {
            $failures[] = implode(PHP_EOL, $output);
        }
        $output = [];
    }
}

if ([] !== $failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "PHP syntax check passed.\n");
