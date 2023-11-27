<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\fields\MissingField;

class MissingFormField extends MissingField implements FormFieldInterface
{
    use FormFieldTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Missing Field');
    }

    public function getExampleInputHtml(): string
    {
        return 'Missing Field';
    }

    public function getSettings(): array
    {
        return [];
    }
}
