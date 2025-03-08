<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;

interface EmailTypeInterface
{
    public static function isEditable(): bool;

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void;

    public function getFieldLayout(): ?FieldLayout;

    /**
     * @todo - Craft requires this because it assumes only Elements have field layouts.
     * This is not true with how we've built the Email Element.
     */
    public static function defaultCardAttributes(): array;
}
