<?php

namespace BarrelStrength\Sprout\uris\links;

use Craft;
use craft\i18n\PhpMessageSource;

trait UriLinkTrait
{
    public function __toString(): string
    {
        return $this->getUrl() ?? static::class;
    }

    abstract public function getUrl(): ?string;
}
