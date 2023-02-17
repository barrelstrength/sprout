<?php

namespace BarrelStrength\Sprout\forms\fields\address;

trait AddressFieldTrait
{
    public string $defaultLanguage = 'en';

    public string $defaultCountry = 'US';

    public bool $showCountryDropdown = true;

    public array $highlightCountries = [];

    /**
     * This will be populated with the addressId if it should be removed from the database
     */
    protected int $_deletedAddressId;

    public function getDeletedAddressId(): ?int
    {
        return $this->_deletedAddressId;
    }

    public function setDeletedAddressId(int $addressId): void
    {
        $this->_deletedAddressId = $addressId;
    }
}
