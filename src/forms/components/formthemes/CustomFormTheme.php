<?php

namespace BarrelStrength\Sprout\forms\components\formthemes;

use BarrelStrength\Sprout\forms\formthemes\FormTheme;
use Craft;

/**
 * The CustomFormTheme is used to dynamically create a FormTheme
 * integration when a user selects the custom option and provides a path
 * to the custom templates they wish to use.
 *
 * The CustomFormTheme integration is not registered with Sprout Forms
 * and will not display in the Form Themes dropdown list.
 *
 * @property string $path
 */
class CustomFormTheme extends FormTheme
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Custom Templates');
    }

    public static function getHandle(): string
    {
        return 'custom';
    }

    public static function isEditable(): bool
    {
        return true;
    }
}



