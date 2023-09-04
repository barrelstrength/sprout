<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use Craft;
use craft\helpers\Cp;

class CurrentPageUrl extends AbstractLink
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Current Page URL');
    }

    public function getUrl(): ?string
    {
        return Craft::$app->getRequest()->getAbsoluteUrl();
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'placeholder' => Craft::t('sprout-module-uris', 'Refresh the page at the same URL on redirect.'),
            'disabled' => true,
        ]);
    }
}
