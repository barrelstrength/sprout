<?php

namespace BarrelStrength\Sprout\forms\components\formtypes;

use BarrelStrength\Sprout\forms\formtypes\FormType;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;

/**
 * The CustomFormType is used to dynamically create a FormType
 * integration when a user selects the custom option and provides a path
 * to the custom templates they wish to use.
 *
 * The CustomFormType integration is not registered with Sprout Forms
 * and will not display in the Form Types dropdown list.
 *
 * @property string $path
 */
class CustomTemplatesFormType extends FormType
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Custom Templates');
    }

    public static function isEditable(): bool
    {
        return true;
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {

    }
}



