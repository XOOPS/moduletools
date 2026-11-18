<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Support;

/**
 * A block as a core before XOOPS 2.7.5 has it: the raw title and name columns, nothing else.
 */
class FakeBlock extends \XoopsObject
{
    /** @var array<string, string> */
    public array $stored;

    public function __construct(string $stored)
    {
        $this->stored = ['title' => $stored, 'name' => $stored];
    }

    public function getVar($key, $format = 's')
    {
        return $this->stored[$key] ?? '';
    }

    public function setVar($key, $value, $not_gpc = false): void
    {
        $this->stored[$key] = (string) $value;
    }
}
