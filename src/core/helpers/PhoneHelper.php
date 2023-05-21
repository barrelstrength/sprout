<?php

namespace BarrelStrength\Sprout\core\helpers;

use CommerceGuys\Addressing\Country\CountryRepository;
use libphonenumber\PhoneNumberUtil;

class PhoneHelper
{
    public static function getCountries(): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $regions = $phoneUtil->getSupportedRegions();
        $countries = [];

        foreach ($regions as $countryCode) {
            $code = $phoneUtil->getCountryCodeForRegion($countryCode);
            $countryRepository = new CountryRepository();
            $country = $countryRepository->get($countryCode);
            $countries[$countryCode] = $country->getName() . ' +' . $code;
        }

        asort($countries);

        return $countries;
    }
}

