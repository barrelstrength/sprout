<?php

namespace BarrelStrength\Sprout\core\components\links;

use craft\elements\Entry;

class EntryElementLink extends AbstractElementLink
{
    public static function elementType(): string
    {
        return Entry::class;
    }
}
