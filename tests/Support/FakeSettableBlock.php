<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Support;

/**
 * A block as XOOPS 2.8 has it: setTitle() keeps a stored language key when the text it
 * displayed comes back unchanged.
 */
final class FakeSettableBlock extends FakeResolvingBlock
{
    /** @var list<string> */
    public array $setTitleCalls = [];

    public function setTitle(string $title): void
    {
        $this->setTitleCalls[] = $title;
        if ($this->key === $this->stored['title'] && $title === $this->shown) {
            return;
        }
        $this->stored['title'] = $title;
    }
}
