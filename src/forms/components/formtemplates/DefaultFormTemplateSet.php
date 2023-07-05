<?php

namespace BarrelStrength\Sprout\forms\components\formtemplates;

use BarrelStrength\Sprout\forms\formtemplates\FormTemplateSet;
use Craft;

class DefaultFormTemplateSet extends FormTemplateSet
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Default Templates');
    }

    public static function getHandle(): string
    {
        return 'default';
    }

    public function getIncludePath(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot/forms/default');
    }
}



