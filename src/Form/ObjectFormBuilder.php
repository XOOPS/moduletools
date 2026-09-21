<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Form;

use Xoops\ModuleTools\Object\DynamicObject;

/** Builds native XOOPS forms from DynamicObject field metadata. */
final class ObjectFormBuilder
{
    public static function build(DynamicObject $object, string $caption = '', string $operation = 'save', string|false $action = false, string|false $submitCaption = false, string|false $cancelAction = false, bool $captcha = false): \XoopsThemeForm
    {
        $action = false === $action ? (string) xoops_getenv('SCRIPT_NAME') : $action;
        $form = new \XoopsThemeForm($caption ?: $object->title('s'), 'moduletools_object_form', $action, 'post', true);
        $form->setExtra('enctype="multipart/form-data"');

        foreach ($object->vars as $name => $metadata) {
            if (
                false === ($metadata['displayOnForm'] ?? true)
                || (false === ($metadata['persistent'] ?? true) && !str_starts_with($name, 'close_section_'))
            ) {
                continue;
            }
            $element = self::element($object, (string) $name, $metadata);
            if (null !== $element) {
                $form->addElement($element, (bool) ($metadata['required'] ?? false));
            }
        }

        if (null !== $object->handler && is_string($object->handler->keyName ?? null)) {
            $key = $object->handler->keyName;
            $form->addElement(new \XoopsFormHidden($key, $object->getVar($key, 'e')));
        }
        $form->addElement(new \XoopsFormHidden('op', $operation));
        if ($captcha && class_exists('XoopsFormCaptcha')) {
            $form->addElement(new \XoopsFormCaptcha(), true);
        }
        $tray = new \XoopsFormElementTray('', ' ');
        $tray->addElement(new \XoopsFormButton('', 'submit', false === $submitCaption ? _SUBMIT : $submitCaption, 'submit'));
        if (false !== $cancelAction) {
            $cancel = new \XoopsFormButton('', 'cancel', _CANCEL, 'button');
            $cancel->setExtra(self::cancelHandler($cancelAction));
            $tray->addElement($cancel);
        }
        $form->addElement($tray);

        return $form;
    }

    /**
     * $cancelAction is a local URL the Cancel button navigates to, or "history.back()".
     * It is never executed as JavaScript: the URL is JSON-encoded into a fixed
     * `location.href = "..."` assignment, and a scheme or host falls back to the current script.
     */
    public static function cancelHandler(string $cancelAction): string
    {
        $cancelAction = trim($cancelAction);
        if (in_array($cancelAction, ['history.back()', 'history.go(-1)'], true)) {
            return "onclick='history.back()'";
        }
        if (
            '' === $cancelAction
            || str_starts_with($cancelAction, '//')
            || str_contains($cancelAction, '\\')
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $cancelAction)
            || 1 === preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $cancelAction)
        ) {
            $cancelAction = (string) xoops_getenv('SCRIPT_NAME');
        }
        $href = json_encode($cancelAction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return "onclick='location.href=" . $href . "'";
    }

    /** @param array<string, mixed> $metadata */
    private static function element(DynamicObject $object, string $name, array $metadata): ?\XoopsFormElement
    {
        $caption = (string) ($metadata['form_caption'] ?: $name);
        $value = $object->getVar($name, 'e');
        $control = $object->getControl($name);
        $controlName = is_array($control) ? (string) ($control['name'] ?? '') : (string) $control;
        $type = (int) ($metadata['custom_type'] ?? $metadata['data_type']);

        if ('yesno' === $controlName) {
            return new \XoopsFormRadioYN($caption, $name, (string) (int) $value);
        }
        if ('user' === $controlName) {
            return new \XoopsFormSelectUser($caption, $name, false, (int) $value);
        }
        if ('datetime' === $controlName || in_array($type, [XOBJ_DTYPE_LTIME, XOBJ_DTYPE_STIME], true)) {
            return new \XoopsFormDateTime($caption, $name, 15, (int) $value);
        }
        if (is_array($control) && isset($control['itemHandler'], $control['method'])) {
            $module = (string) ($control['module'] ?? ($object->handler->_moduleName ?? ''));
            if ('' === $module) {
                return null;
            }
            $helper = \Xmf\Module\Helper::getHelper($module);
            $handler = $helper->getHandler((string) $control['itemHandler']);
            $options = method_exists($handler, (string) $control['method']) ? $handler->{$control['method']}() : [];
            $select = new \XoopsFormSelect($caption, $name, $value);
            $select->addOptionArray((array) $options);
            return $select;
        }
        if (in_array($controlName, ['select', 'select_multi', 'radio', 'check'], true)) {
            $options = is_array($control) ? (array) ($control['options'] ?? []) : [];
            if ('radio' === $controlName) {
                $element = new \XoopsFormRadio($caption, $name, $value);
            } elseif ('check' === $controlName) {
                $element = new \XoopsFormCheckBox($caption, $name, $value);
            } else {
                $element = new \XoopsFormSelect($caption, $name, $value, 1, 'select_multi' === $controlName);
            }
            $element->addOptionArray($options);
            return $element;
        }
        if (XOBJ_DTYPE_FORM_SECTION === $type) {
            return new \XoopsFormLabel('', '<h3>' . htmlspecialchars($caption, ENT_QUOTES | ENT_HTML5) . '</h3>');
        }
        if (XOBJ_DTYPE_FORM_SECTION_CLOSE === $type) {
            return null;
        }
        if (in_array($type, [XOBJ_DTYPE_TXTAREA], true) || 'textarea' === $controlName) {
            return new \XoopsFormDhtmlTextArea($caption, $name, (string) $value, 8, 60);
        }
        if (in_array($type, [XOBJ_DTYPE_FILE, XOBJ_DTYPE_IMAGE], true) || in_array($controlName, ['file', 'image', 'richfile'], true)) {
            $tray = new \XoopsFormElementTray($caption, '<br>');
            if ('' !== (string) $value) {
                $tray->addElement(new \XoopsFormLabel('', htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5)));
            }
            $tray->addElement(new \XoopsFormFile('', $name, 0));
            return $tray;
        }

        $size = min(80, max(5, (int) ($metadata['size'] ?? 50)));
        $maxLength = max($size, (int) ($metadata['maxlength'] ?? 255));
        return new \XoopsFormText($caption, $name, $size, $maxLength, (string) $value);
    }
}
