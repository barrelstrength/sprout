<?php

namespace BarrelStrength\Sprout\forms\components\formtemplates;

use BarrelStrength\Sprout\forms\formtemplates\FormTemplateSet;
use Craft;

/**
 * The Custom Templates is used to dynamically create a FormTemplates
 * integration when a user selects the custom option and provides a path
 * to the custom templates they wish to use.
 *
 * The Custom Templates integration is not registered with Sprout Forms
 * and will not display in the Form Templates dropdown list.
 *
 * @property string $path
 */
class CustomFormTemplateSet extends FormTemplateSet
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Custom Templates');
    }

    public static function getHandle(): string
    {
        return 'custom';
    }

    public function getIncludePath(): string
    {

        return '';
    }
}



