<?php

namespace BarrelStrength\Sprout\forms\formthemes;

use craft\base\SavableComponent;

abstract class FormTheme extends SavableComponent implements FormThemeInterface
{
    public ?string $name = null;

    public ?string $formTemplate = null;

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

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'formTemplate' => $this->formTemplate,
        ];

        return $config;
    }
}
