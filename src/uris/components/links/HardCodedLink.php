<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\UriLinkTrait;
use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class HardCodedLink extends AbstractLink
{
    use UriLinkTrait;

    /**
     * The absolute URL the link resolves to
     */
    public ?string $url = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-uris', 'URL');
    }

    public function getInputHtml(): ?string
    {
        return Cp::textHtml([
            'name' => static::class.'[url]',
            'placeholder' => UrlHelper::siteUrl(),
            'value' => $this->url,
            'errors' => '',
        ]);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
