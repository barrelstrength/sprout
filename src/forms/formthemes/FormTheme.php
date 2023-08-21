<?php

namespace BarrelStrength\Sprout\forms\formthemes;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use craft\base\SavableComponent;
use craft\models\FieldLayout;

abstract class FormTheme extends SavableComponent implements FormThemeInterface
{
    public ?string $name = null;

    public ?string $formTemplate = null;

    public ?string $formTemplateOverrideFolder = null;

    protected ?FieldLayout $_fieldLayout = null;

    public ?string $uid = null;

    public static function isEditable(): bool
    {
        return false;
    }

    /**
     * Adds pre-defined options for css classes.
     *
     * These classes will display in the CSS Classes dropdown list on the Field Edit modal
     * for Field Types that support the $cssClasses property.
     */
    public function getCssClassDefaults(): array
    {
        return [];
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $fieldLayout = new FieldLayout([
            'type' => FormElement::class,
        ]);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'formTemplate' => $this->formTemplate,
            'formTemplateOverrideFolder' => $this->formTemplateOverrideFolder,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
