<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

abstract class FormTemplateSet
{
    /**
     * The name of your Form Templates
     */
    abstract public function getName(): string;

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
}
