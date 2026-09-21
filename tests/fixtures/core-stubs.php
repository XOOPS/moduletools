<?php

declare(strict_types=1);

if (!class_exists('XoopsObject', false)) {
    class XoopsObject
    {
    }
}

if (!class_exists('XoopsObjectTree', false)) {
    class XoopsObjectTree
    {
    }
}

if (!class_exists('XoopsPersistableObjectHandler', false)) {
    class XoopsPersistableObjectHandler
    {
    }
}

if (!function_exists('redirect_header')) {
    // The library redirects instead of writing; in tests the redirect becomes an exception.
    function redirect_header(string $url, int $time = 0, string $message = ''): never
    {
        throw new \RuntimeException($message);
    }
}
