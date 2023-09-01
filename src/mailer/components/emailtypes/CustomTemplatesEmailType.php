<?php

namespace BarrelStrength\Sprout\mailer\components\emailtypes;

use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use Craft;
use craft\fieldlayoutelements\Tip;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

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

    public function createFieldLayout(): ?FieldLayout
    {
        return new FieldLayout([
            'type' => self::class,
        ]);
    }
}



