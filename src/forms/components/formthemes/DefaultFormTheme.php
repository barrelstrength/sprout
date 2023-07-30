<?php

namespace BarrelStrength\Sprout\forms\components\formthemes;

use BarrelStrength\Sprout\forms\formthemes\FormTheme;
use Craft;

class DefaultFormTheme extends FormTheme
{
    public ?string $formTemplate = '@Sprout/TemplateRoot/forms/default';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Default Templates');
    }
}



