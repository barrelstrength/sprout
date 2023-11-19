<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;

class ThingSchema extends Schema
{
    public function getName(): string
    {
        return 'Thing';
    }

    public function getType(): string
    {
        return 'Thing';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $metadata = $this->prioritizedMetadataModel;

        if ($this->isMainEntity) {
            $this->addMainEntityOfPage();
        }

        $this->addText('name', $metadata->getOptimizedTitle());
        $this->addText('description', $metadata->getOptimizedDescription());
        $this->addImage('image', $metadata->getOptimizedImage());
        $this->addUrl('url', $metadata->getCanonical());
    }
}
