<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\base\Element;

class ElementUriHelper
{
    public static function getElementTypesWithUris(): array
    {
        /** @var Element[] $types */
        $types = Craft::$app->getElements()->getAllElementTypes();

        $uriTypes = [];

        foreach ($types as $type) {
            if (!$type::hasUris()) {
                continue;
            }

            $uriTypes[] = $type;
        }

        return $uriTypes;
    }

    public static function getElementsWithUrisForSitemaps(array $allowedElementTypes = []): array
    {
        $elementsWithUris = SitemapsModule::getInstance()->sitemaps->getElementWithUris();

        $elementsWithUris = array_filter($elementsWithUris, static function($element) use ($allowedElementTypes) {
            return in_array($element::class, $allowedElementTypes, true);
        });

        return $elementsWithUris;
    }
}
