<?php

namespace BarrelStrength\Sprout\forms\fields\address;

use CommerceGuys\Addressing\Country\CountryRepository;

/**
 * This class is only necessary because the availableLocales that
 * access below are a protected method in the Country Repository class
 */
class CountryRepositoryHelper extends CountryRepository
{
    /**
     * Helper method to retrieve protected property on parent class
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }
}
