<?php

namespace BarrelStrength\Sprout\meta\components\schema;

class IntangibleSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Intangible';
    }

    public function getType(): string
    {
        return 'Intangible';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }
}
