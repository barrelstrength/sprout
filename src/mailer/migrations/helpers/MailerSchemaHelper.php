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
    public static function createEmailTypeIfNoTypeExists(string $type, array $config = [], array $matchConfig = []): EmailType
    {
        $emailTypes = EmailTypeHelper::getEmailTypes();

        // Give preference to the matchConfig if it exists
        $matchParams = !empty($matchConfig) ? $matchConfig : $config;

        foreach ($emailTypes as $emailType) {
            if (!$emailType instanceof $type) {
                continue;
            }

            $matches = 0;
            foreach ($matchParams as $attribute => $param) {
                if ($emailType->{$attribute} === $param) {
                    $matches++;
                }
            }

            if ($matches === count($matchParams)) {
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
