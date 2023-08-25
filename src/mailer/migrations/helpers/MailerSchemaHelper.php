<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailtypes\CustomTemplatesEmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use craft\helpers\StringHelper;
use ReflectionClass;

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

    public static function createEmailTypeIfNoTypeExists(string $type, array $config = []): EmailType
    {
        $emailTypes = EmailTypeHelper::getEmailTypes();

        foreach ($emailTypes as $emailType) {
            $matchingCustomTemplates =
                $emailType instanceof CustomTemplatesEmailType
                && $emailType->htmlEmailTemplate === $config['htmlEmailTemplate'];

            if ($emailType instanceof $type || $matchingCustomTemplates) {
                return $emailType;
            }
        }

        $emailType = new $type($config);
        $emailType->uid = StringHelper::UUID();

        if (!$emailType->name) {
            $reflection = new ReflectionClass($emailType);
            $words = preg_split('/(?=[A-Z])/', $reflection->getShortName());
            $string = implode(' ', $words);
            $emailType->name = trim($string);
        }

        $emailTypes[$emailType->uid] = $emailType;

        EmailTypeHelper::saveEmailTypes($emailTypes);

        return $emailType;
    }
}
