<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;

interface MailerInterface
{
    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): array;

    public static function defineNativeElements(DefineFieldLayoutElementsEvent $event): array;

    /**
     * @todo - Craft requires this because it assumes only Elements have field layouts.
     * This is not true with how we've built the Email Element.
     */
    public static function defaultCardAttributes(): array;
}
