<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;

class CustomEmailTheme extends EmailTheme
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Custom');
    }

    public static function getHandle(): string
    {
        return 'custom';
    }

    public static function isEditable(): bool
    {
        return true;
    }

    public function getIncludePath(): string
    {
        //$settings = MailerModule::getInstance()->getSettings();

        return Craft::getAlias('@Sprout/TemplateRoot/email/default');
    }
}



