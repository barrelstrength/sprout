<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\schema\Schema;

class PostalAddressSchema extends Schema
{
    public ?string $addressCountry = null;

    public ?string $addressLocality = null;

    public ?string $addressRegion = null;

    public ?string $postalCode = null;

    public ?string $streetAddress = null;

    public function getName(): string
    {
        return 'Postal Address';
    }

    public function getType(): string
    {
        return 'PostalAddress';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $this->addText('addressCountry', $this->addressCountry);
        $this->addText('addressLocality', $this->addressLocality);
        $this->addText('addressRegion', $this->addressRegion);
        $this->addText('postalCode', $this->postalCode);
        $this->addText('streetAddress', $this->streetAddress);
    }
}
