<?php

declare(strict_types=1);

if (!class_exists('XoopsObject')) {
    class XoopsObject
    {
    }
}

if (!class_exists('XoopsObjectTree')) {
    class XoopsObjectTree
    {
    }
}

if (!class_exists('XoopsPersistableObjectHandler')) {
    class XoopsPersistableObjectHandler
    {
    }
}

if (!class_exists('XoopsUser')) {
    /** Just enough of a user for the admin gates: isAdmin() answers what the test sets. */
    class XoopsUser
    {
        public function __construct(private readonly bool $admin = false)
        {
        }

        public function isAdmin(): bool
        {
            return $this->admin;
        }
    }
}

if (!function_exists('redirect_header')) {
    // The library redirects instead of writing; in tests the redirect becomes an exception.
    function redirect_header(string $url, int $time = 0, string $message = ''): never
    {
        throw new \RuntimeException($message);
    }
}

if (!function_exists('xoops_getenv')) {
    function xoops_getenv(string $key): string
    {
        return (string) ($_SERVER[$key] ?? '');
    }
}
