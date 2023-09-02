<?php

namespace BarrelStrength\Sprout\mailer\components\emailtypes;

use BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields\DefaultMessageField;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;

class CustomTemplatesEmailType extends EmailType
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Custom Templates');
    }

    public static function isEditable(): bool
    {
        return true;
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        $event->fields[DefaultMessageField::class] = DefaultMessageField::class;
    }

    public function createFieldLayout(): ?FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => self::class,
        ]);

        return $fieldLayout;
    }
}



