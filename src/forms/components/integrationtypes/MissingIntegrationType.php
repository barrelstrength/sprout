<?php

namespace BarrelStrength\Sprout\forms\components\integrationtypes;

use BarrelStrength\Sprout\forms\integrations\Integration;
use Craft;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingIntegrationType extends Integration implements MissingComponentInterface
{
    use MissingComponentTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Missing Integration');
    }
}
