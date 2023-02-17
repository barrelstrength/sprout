<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;

class MainEntityOfPageSchema extends Schema
{
    public int $id;

    public function getName(): string
    {
        return 'Main Entity Of Page';
    }

    public function getType(): string
    {
        return 'WebPage';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $this->addProperty('@id', $this->id);
    }
}
