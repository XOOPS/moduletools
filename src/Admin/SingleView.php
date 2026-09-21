<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Admin;

final class SingleView
{
    /** @var list<ObjectRow> */
    private array $rows = [];

    public function __construct(private readonly \XoopsObject $object, private readonly bool $userSide = false, private readonly array $actions = [], private readonly bool $headerAsRow = true)
    {
    }

    public function addRow(ObjectRow $row): void
    {
        $this->rows[] = $row;
    }

    public function fetch(bool $debug = false): string
    {
        return $this->render(true, $debug);
    }

    public function render(bool $fetchOnly = false, bool $debug = false): ?string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $html = '<dl class="moduletools-single-view">';
        foreach ($this->rows as $row) {
            $value = false !== $row->valueMethod && method_exists($this->object, $row->valueMethod)
                ? $this->object->{$row->valueMethod}()
                : $this->object->getVar($row->key, 's');
            $caption = $this->object->vars[$row->key]['form_caption'] ?? $row->key;
            $html .= '<dt>' . $escape($caption) . '</dt><dd>' . $escape($value) . '</dd>';
        }
        $html .= '</dl>';
        if ($fetchOnly) {
            return $html;
        }
        echo $html;
        return null;
    }
}
