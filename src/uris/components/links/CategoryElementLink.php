<?php

namespace BarrelStrength\Sprout\uris\components\links;

use BarrelStrength\Sprout\uris\links\AbstractLink;
use BarrelStrength\Sprout\uris\links\ElementLinkInterface;
use BarrelStrength\Sprout\uris\links\ElementLinkTrait;
use craft\elements\Category;

class CategoryElementLink extends AbstractLink implements ElementLinkInterface
{
    use ElementLinkTrait;

    public static function elementType(): string
    {
        return Category::class;
    }
}
