<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Support;

/**
 * A XoopsObject stand-in that reproduces core's getVar() contract (kernel/object.php):
 * TXTBOX is HTML-escaped for 's'/'e', TXTAREA is escaped for 'e' and "displayed" for 's',
 * every other type comes back raw whatever the format; 'n' is always the stored value.
 */
final class FakeObject extends \XoopsObject
{
    /** @var array<string, array<string, mixed>> */
    public array $vars = [];
    /** @var list<array{string, mixed}> */
    public array $setCalls = [];

    public function addVar(string $key, int $dataType, mixed $value, array $extra = []): self
    {
        $this->vars[$key] = $extra + ['data_type' => $dataType, 'value' => $value, 'persistent' => true];

        return $this;
    }

    public function getVar($key, $format = 's')
    {
        $value = $this->vars[$key]['value'] ?? null;
        $type = (int) ($this->vars[$key]['data_type'] ?? 0);
        if ('n' === $format) {
            return $value;
        }
        if (XOBJ_DTYPE_TXTBOX === $type || (XOBJ_DTYPE_TXTAREA === $type && 'e' === $format)) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5);
        }
        if (XOBJ_DTYPE_TXTAREA === $type) {
            return nl2br((string) $value); // stands in for displayTarea()
        }

        return $value;
    }

    public function setVar($key, $value, $not_gpc = false)
    {
        $this->setCalls[] = [(string) $key, $value];
        $this->vars[$key]['value'] = $value;
    }
}
