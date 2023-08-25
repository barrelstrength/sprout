<?php

namespace BarrelStrength\Sprout\mailer\components\emailtypes;

use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use Craft;

class CustomTemplatesEmailType extends EmailType
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



