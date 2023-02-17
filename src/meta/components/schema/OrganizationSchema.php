<?php

namespace BarrelStrength\Sprout\meta\components\schema;

class OrganizationSchema extends ThingSchema
{
    public function getName(): string
    {
        return 'Organization';
    }

    public function getType(): string
    {
        return 'Organization';
    }

    public function isUnlistedSchemaType(): bool
    {
        return false;
    }
}
