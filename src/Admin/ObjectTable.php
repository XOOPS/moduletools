<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Admin;

/**
 * Transitional table presenter for modules moving from SmartObject.
 * New screens should expose the same rows through XMF DataTable builders.
 */
class ObjectTable
{
    /** @var list<ObjectColumn> */
    protected array $columns = [];
    /** @var list<string> */
    protected array $customActions = [];
    /** @var list<string> */
    protected array $actions;
    protected array $introButtons = [];
    protected array $filters = [];
    protected string $tableId = 'moduletools-object-table';
    protected bool $showTools = true;
    protected ?array $objects = null;

    public function __construct(
        protected readonly \XoopsPersistableObjectHandler $handler,
        protected ?\CriteriaCompo $criteria = null,
        array $actions = ['edit', 'delete'],
        protected bool $userSide = false,
    ) {
        $this->actions = array_values(array_map(strval(...), $actions));
    }

    public function addColumn(ObjectColumn $column): void
    {
        $this->columns[] = $column;
    }
    public function addCustomAction(string $method): void
    {
        $this->customActions[] = $method;
    }
    public function addIntroButton(string $name, string $location, string $value): void
    {
        $this->introButtons[] = compact('name', 'location', 'value');
    }
    public function addActionButton(string $operation, string|false $caption = false, string|false $text = false): void
    {
        $this->actions[] = $operation;
    }
    public function addWithSelectedActions(array $actions = []): void
    {
    }
    public function addFilter(string $key, string $method, mixed $default = false): void
    {
        $this->filters[$key] = compact('method', 'default');
    }
    public function setDefaultFilter(mixed $filter): void
    {
    }
    public function setDefaultFilter2(mixed $filter): void
    {
    }
    public function setTableId(string $id): void
    {
        $this->tableId = $id;
    }
    public function setObjects(array $objects): void
    {
        $this->objects = $objects;
    }
    public function setDefaultSort(string $sort): void
    {
        $this->criteria ??= new \CriteriaCompo();
        $this->criteria->setSort($sort);
    }
    public function setDefaultOrder(string $order): void
    {
        $this->criteria ??= new \CriteriaCompo();
        $this->criteria->setOrder('DESC' === strtoupper($order) ? 'DESC' : 'ASC');
    }
    public function hideFilterAndLimit(): void
    {
        $this->showTools = false;
    }
    public function disableColumnsSorting(): void
    {
    }
    public function isForUserSide(): void
    {
        $this->userSide = true;
    }

    public function fetch(bool $debug = false): string
    {
        return $this->buildHtml();
    }

    public function render(bool $fetchOnly = false, bool $debug = false): ?string
    {
        $html = $this->buildHtml();
        if ($fetchOnly) {
            return $html;
        }
        echo $html;
        return null;
    }

    /** @return list<\XoopsObject> */
    protected function fetchObjects(): array
    {
        if (null !== $this->objects) {
            return array_values(array_filter($this->objects, static fn ($object): bool => $object instanceof \XoopsObject));
        }
        return array_values($this->handler->getObjects($this->criteria));
    }

    protected function buildHtml(): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $html = '';
        foreach ($this->introButtons as $button) {
            $html .= '<p><a class="btn btn-primary" href="' . $escape($button['location']) . '">' . $escape($button['value']) . '</a></p>';
        }
        $html .= '<table id="' . $escape($this->tableId) . '" class="outer table table-striped"><thead><tr>';
        foreach ($this->columns as $column) {
            $caption = false !== $column->caption ? $column->caption : ($this->handler->create()->vars[$column->key]['form_caption'] ?? $column->key);
            $html .= '<th class="' . $escape($column->align) . '">' . $escape($caption) . '</th>';
        }
        if ([] !== $this->actions || [] !== $this->customActions) {
            $html .= '<th class="center">' . $escape(defined('_ACTION') ? _ACTION : 'Action') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        $key = (string) $this->handler->keyName;
        foreach ($this->fetchObjects() as $index => $object) {
            $html .= '<tr class="' . (0 === $index % 2 ? 'even' : 'odd') . '">';
            foreach ($this->columns as $column) {
                $method = $column->valueMethod;
                $isCustom = false !== $method && method_exists($object, $method);
                $value = $isCustom ? $object->{$method}(...(false === $column->parameters ? [] : $column->parameters)) : $object->getVar($column->key, 's');
                $html .= '<td class="' . $escape($column->align) . '">' . ($isCustom ? (string) $value : $escape($value)) . '</td>';
            }
            if ([] !== $this->actions || [] !== $this->customActions) {
                $id = (int) $object->getVar($key, 'n');
                $script = $escape(xoops_getenv('SCRIPT_NAME'));
                $links = [];
                foreach ($this->actions as $action) {
                    $operation = 'edit' === $action ? 'mod' : ('delete' === $action ? 'del' : $action);
                    $links[] = '<a href="' . $script . '?op=' . rawurlencode($operation) . '&amp;' . rawurlencode($key) . '=' . $id . '">' . $escape(ucfirst($action)) . '</a>';
                }
                foreach ($this->customActions as $method) {
                    if (method_exists($object, $method)) {
                        $links[] = (string) $object->{$method}();
                    }
                }
                $html .= '<td class="center">' . implode(' ', $links) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
}
