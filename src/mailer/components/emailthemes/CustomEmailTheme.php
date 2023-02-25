<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;

class CustomEmailTheme extends EmailTheme
{
    /**
     * Handle will be defined in settings
     */
    public ?string $handle = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Custom Theme');
    }

    public function getIncludePath(): string
    {
        //$settings = MailerModule::getInstance()->getSettings();

        return Craft::getAlias('@Sprout/TemplateRoot/email/default');
    }
}



