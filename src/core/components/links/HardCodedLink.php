<?php

namespace BarrelStrength\Sprout\core\components\links;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;

class HardCodedLink extends AbstractLink
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'URL');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => 'url',
            'placeholder' => UrlHelper::siteUrl(),
            'value' => '',
            'errors' => '',
        ]);
    }
}
