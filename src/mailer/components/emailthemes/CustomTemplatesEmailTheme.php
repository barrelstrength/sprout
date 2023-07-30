<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;

class CustomTemplatesEmailTheme extends EmailTheme
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Custom Templates');
    }

    public static function isEditable(): bool
    {
        return true;
    }
}



