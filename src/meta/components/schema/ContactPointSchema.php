<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;

class ContactPointSchema extends Schema
{
    public array $contact;

    public function getName(): string
    {
        return 'Contact Point';
    }

    public function getType(): string
    {
        return 'ContactPoint';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $contact = $this->contact;

        if (!$contact) {
            return;
        }

        $this->addText('contactType', $contact['contactType']);
        $this->addText('telephone', $contact['telephone']);
    }
}
