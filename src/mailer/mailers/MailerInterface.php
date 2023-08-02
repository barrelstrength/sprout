<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\events\DefineFieldLayoutFieldsEvent;

interface MailerInterface
{
    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): array;
}
