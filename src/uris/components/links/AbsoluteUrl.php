<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;

class AbsoluteUrl extends AbstractLink
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Absolute URL');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => static::class . '[url]',
            'placeholder' => UrlHelper::siteUrl(),
            'value' => $this->url,
            'errors' => '',
        ]);
    }
}
