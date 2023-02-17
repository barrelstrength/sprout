<?php

namespace BarrelStrength\Sprout\meta\components\schema;

class PlaceSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Place';
    }

    public function getType(): string
    {
        return 'Place';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }
}
