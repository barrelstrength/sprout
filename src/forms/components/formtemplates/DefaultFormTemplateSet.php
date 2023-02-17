<?php

namespace BarrelStrength\Sprout\forms\components\formtemplates;

use BarrelStrength\Sprout\forms\formtemplates\FormTemplateSet;
use Craft;

class DefaultFormTemplateSet extends FormTemplateSet
{
    public function getName(): string
    {
        return Craft::t('sprout-module-forms', 'Default Templates');
    }

    public function getIncludePath(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot/forms/default');
    }
}



