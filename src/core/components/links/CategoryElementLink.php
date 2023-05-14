<?php

namespace BarrelStrength\Sprout\core\components\links;

use craft\elements\Category;

class CategoryElementLink extends AbstractElementLink
{
    public static function elementType(): string
    {
        return Category::class;
    }
}
