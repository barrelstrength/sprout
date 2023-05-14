<?php

namespace BarrelStrength\Sprout\core\components\links;

use Craft;
use craft\helpers\Cp;

class EmailLink extends AbstractLink
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Email');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'type' => 'email',
            'placeholder' => Craft::$app->getUser()->getIdentity()->email,
            'name' => 'email',
            'value' => '',
            'errors' => '',
        ]);
    }
}
