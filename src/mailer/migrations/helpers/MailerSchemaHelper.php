<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailthemes\CustomTemplatesEmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use craft\helpers\StringHelper;

class MailerSchemaHelper
{
    public static function createDefaultMailerIfNoTypeExists(string $emailVariantType, string $mailerType): Mailer
    {
        $mailers = MailerHelper::getMailers();

        foreach ($mailers as $mailer) {
            if ($mailer instanceof $mailerType) {
                return $mailer;
            }
        }

        /** @var EmailVariant $emailVariant */
        $emailVariant = new $emailVariantType();
        $mailer = $emailVariant::createDefaultMailer();

        $mailers[$mailer->uid] = $mailer;

        MailerHelper::saveMailers($mailers);

        return $mailer;
    }

    public static function createEmailThemeIfNoTypeExists(string $type, array $config = []): EmailTheme
    {
        $emailThemes = EmailThemeHelper::getEmailThemes();

        foreach ($emailThemes as $emailTheme) {
            $matchingCustomTemplates =
                $emailTheme instanceof CustomTemplatesEmailTheme
                && $emailTheme->htmlEmailTemplate === $config['htmlEmailTemplate'];

            if ($emailTheme instanceof $type || $matchingCustomTemplates) {
                return $emailTheme;
            }
        }

        $emailTheme = new $type($config);
        $emailTheme->uid = StringHelper::UUID();

        if (!$emailTheme->name) {
            $reflection = new \ReflectionClass($emailTheme);
            $words = preg_split('/(?=[A-Z])/', $reflection->getShortName());
            $string = implode(' ', $words);
            $emailTheme->name = trim($string);
        }

        $emailThemes[$emailTheme->uid] = $emailTheme;

        EmailThemeHelper::saveEmailThemes($emailThemes);

        return $emailTheme;
    }
}
