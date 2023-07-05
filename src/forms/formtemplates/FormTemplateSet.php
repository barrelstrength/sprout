<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

use craft\base\SavableComponent;

abstract class FormTemplateSet extends SavableComponent implements FormTemplateSetInterface
{
    public ?string $name = null;

    public ?string $uid = null;

    /**
     * The name of your Form Templates
     */
    public function name(): ?string
    {
        return $this->name ?? self::displayName();
    }

    abstract public static function getHandle(): string;

    public static function isEditable(): bool
    {
        return false;
    }

    public function getIncludePath(): string
    {
        return '';
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
            'handle' => $this::getHandle(),
            //'htmlEmailTemplate' => $this->htmlEmailTemplate,
            //'textEmailTemplate' => $this->textEmailTemplate,
            //'copyPasteEmailTemplate' => $this->copyPasteEmailTemplate,
        ];

        //$fieldLayout = $this->getFieldLayout();
        //
        //if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
        //    $config['fieldLayouts'] = [
        //        $fieldLayout->uid => $fieldLayoutConfig,
        //    ];
        //}

        return $config;
    }
}
