<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use BarrelStrength\Sprout\uris\links\LinkTrait;
use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;

class RelativeUrl extends AbstractLink
{
    use LinkTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'Relative URL');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => $this->namespaceInputName('url'),
            'placeholder' => '/thank-you',
            'value' => $this->url,
        ]);
    }
}
