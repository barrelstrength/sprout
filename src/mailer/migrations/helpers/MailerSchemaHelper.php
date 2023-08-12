<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use craft\helpers\StringHelper;

class MailerSchemaHelper
{
    public static function createDefaultMailer(EmailType $emailType): void
    {
        $emailType::createDefaultMailer();
    }

    public static function createDefaultEmailTheme(): void
    {
        $emailTheme = new EmailMessageTheme();
        $emailTheme->name = 'Simple Message';
        $emailTheme->uid = StringHelper::UUID();

        EmailThemeHelper::saveEmailThemes([
            $emailTheme->uid => $emailTheme,
        ]);
    }
}
