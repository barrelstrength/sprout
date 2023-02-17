<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;

class GeoSchema extends Schema
{
    public string $latitude;

    public string $longitude;

    public function getName(): string
    {
        return 'Geo';
    }

    public function getType(): string
    {
        return 'GeoCoordinates';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $this->addText('latitude', $this->latitude);
        $this->addText('longitude', $this->longitude);
    }
}
