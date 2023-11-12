<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;

interface EmailTypeInterface
{
    public static function isEditable(): bool;

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void;

    public function getFieldLayout(): ?FieldLayout;
}
