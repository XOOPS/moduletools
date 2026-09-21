<?php

declare(strict_types=1);

// Package-local vendor/ when developed standalone; Core's xoops_lib/vendor/ when installed there.
$autoload = is_file(dirname(__DIR__) . '/vendor/autoload.php')
    ? dirname(__DIR__) . '/vendor/autoload.php'
    : dirname(__DIR__, 3) . '/autoload.php';
require $autoload;
require __DIR__ . '/fixtures/core-stubs.php';
