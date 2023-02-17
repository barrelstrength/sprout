<?php

namespace BarrelStrength\Sprout\meta\components\schema;

class EventSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Event';
    }

    public function getType(): string
    {
        return 'Event';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }
}
