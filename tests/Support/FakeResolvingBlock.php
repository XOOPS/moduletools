<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Support;

/**
 * A block as XOOPS 2.7.5 has it: title() and name() translate one stored language key;
 * there is no setTitle().
 */
class FakeResolvingBlock extends FakeBlock
{
    public function __construct(string $stored, protected readonly string $key, protected readonly string $shown)
    {
        parent::__construct($stored);
    }

    public function title($format = ''): string
    {
        return $this->resolve('title');
    }

    public function name($format = ''): string
    {
        return $this->resolve('name');
    }

    private function resolve(string $column): string
    {
        return $this->key === $this->stored[$column] ? $this->shown : $this->stored[$column];
    }
}
