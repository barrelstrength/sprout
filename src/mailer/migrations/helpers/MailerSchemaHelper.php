<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailtypes\CustomTemplatesEmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use craft\helpers\StringHelper;
use ReflectionClass;

class MailerSchemaHelper
{
    public static function createEmailTypeIfNoTypeExists(string $type, array $config = []): EmailType
    {
        $emailTypes = EmailTypeHelper::getEmailTypes();

        foreach ($emailTypes as $emailType) {
            $matchingCustomTemplates =
                $emailType instanceof CustomTemplatesEmailType
                && $emailType->htmlEmailTemplate === $config['htmlEmailTemplate'];

            if ($matchingCustomTemplates) {
                return $emailType;
            }

            if ($emailType instanceof $type && !$emailType instanceof CustomTemplatesEmailType) {
                return $emailType;
            }
        }

        $emailType = new $type($config);
        $emailType->mailerUid = MailerHelper::CRAFT_MAILER_SETTINGS;
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
