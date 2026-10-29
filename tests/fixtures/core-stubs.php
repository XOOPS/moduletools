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
    /** Just enough of a user for the admin gates: isAdmin() and getGroups() answer what the test sets. */
    class XoopsUser
    {
        /** @param list<int|string> $groups core returns group ids as strings */
        public function __construct(private readonly bool $admin = false, private readonly array $groups = [])
        {
        }

        public function isAdmin(): bool
        {
            return $this->admin;
        }

        /** @return list<int|string> */
        public function getGroups(): array
        {
            return $this->groups;
        }
    }
}

if (!class_exists('XoopsMediaUploader')) {
    /** Records which upload fields reached the uploader and were moved; every upload "succeeds" as "saved-<field>". */
    class XoopsMediaUploader
    {
        /** @var list<string> */
        public static array $fetched = [];
        /** @var list<string> */
        public static array $uploaded = [];
        /** @var list<string> fields whose file fails core's type check (run by upload() and the preflight) */
        public static array $badType = [];
        public ?string $mediaName = null;
        public ?string $mediaTmpName = null;
        public ?string $targetFileName = null;
        /** @var array<int, string> as core, abridged */
        public array $imageExtensions = [1 => 'gif', 2 => 'jpg', 3 => 'png'];
        private string $field = '';
        /** @var list<string> */
        private array $errors = [];

        public function checkMimeType(): bool
        {
            return !in_array($this->field, self::$badType, true);
        }

        public function __construct(string $uploadDir, array $allowedMimeTypes, int $maxFileSize, int $maxWidth = 0, int $maxHeight = 0)
        {
        }

        public function fetchMedia(string $field): bool
        {
            self::$fetched[] = $this->field = $field;
            $this->mediaName = $_FILES[$field]['name'] ?? null;
            $this->mediaTmpName = $_FILES[$field]['tmp_name'] ?? null;

            return true;
        }

        public function setErrors(string $error): void
        {
            $this->errors[] = $error;
        }

        public function upload(): bool
        {
            if (!$this->checkMimeType()) {
                return false;
            }
            self::$uploaded[] = $this->field;

            return true;
        }

        public function getSavedFileName(): string
        {
            return 'saved-' . $this->field;
        }

        /** @return list<string> */
        public function getErrors(bool $ashtml = true): array
        {
            return $this->checkMimeType() ? $this->errors : [...$this->errors, $this->field . ': type not allowed'];
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
