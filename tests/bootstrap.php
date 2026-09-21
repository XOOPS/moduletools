<?php

declare(strict_types=1);

// Package-local vendor/ when developed standalone; Core's xoops_lib/vendor/ when installed there.
$autoload = is_file(dirname(__DIR__) . '/vendor/autoload.php')
    ? dirname(__DIR__) . '/vendor/autoload.php'
    : dirname(__DIR__, 3) . '/autoload.php';
require $autoload;
require __DIR__ . '/fixtures/core-stubs.php';

// Site constants for the whole run, in one place (file-scope defines in a test would apply
// in file-discovery order). The root path carries "&" so output tests prove their escaping;
// the upload path is real so filesystem tests can create directories under it.
defined('XOOPS_ROOT_PATH') || define('XOOPS_ROOT_PATH', 'C:/test&root');
defined('XOOPS_URL') || define('XOOPS_URL', 'http://localhost');
defined('XOOPS_UPLOAD_PATH') || define('XOOPS_UPLOAD_PATH', sys_get_temp_dir());
