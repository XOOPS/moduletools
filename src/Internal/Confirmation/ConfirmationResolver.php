<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Internal\Confirmation;

/** @internal Compatibility implementation detail; not part of the ModuleTools public API. */
final class ConfirmationResolver
{
    /** @param array<string, mixed> $hiddens */
    public function resolve(
        array $hiddens,
        string $action,
        string $requestUri,
        string $object,
        string $title,
        string $label,
        ?string $moduleDirName,
    ): ConfirmationModel {
        if (null !== $moduleDirName && '' !== $moduleDirName) {
            $upper = mb_strtoupper($moduleDirName);
            if (!defined('_CO_' . $upper . '_DELETE_CONFIRM')) {
                define('_CO_' . $upper . '_DELETE_CONFIRM', 'Confirm delete');
                define('_CO_' . $upper . '_DELETE_LABEL', 'Do you really want to delete:');
            }
            if ('' === $title) {
                $title = (string) constant('_CO_' . $upper . '_DELETE_CONFIRM');
            }
            if ('' === $label) {
                $label = (string) constant('_CO_' . $upper . '_DELETE_LABEL');
            }
        }
        $title = '' === $title ? 'Confirm delete' : $title;
        $label = '' === $label ? 'Do you really want to delete:' : $label;

        return new ConfirmationModel(
            hiddens: $hiddens,
            action: '' === $action ? $requestUri : $action,
            object: $object,
            title: $title,
            label: $label,
        );
    }
}
