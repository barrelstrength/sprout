<?php

namespace BarrelStrength\Sprout\fields\helpers;

use BarrelStrength\Sprout\forms\fields\address\Addresses;
use CommerceGuys\Addressing\Country\CountryRepository;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Craft;

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

    public static function validatePhone(mixed $phone, mixed $country = Addresses::DEFAULT_COUNTRY): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($phone, $country);

            if (!$phoneNumber) {
                throw new NumberParseException(400, 'Unable to parse phone number.');
            }

            $isValid = $phoneUtil->isValidNumber($phoneNumber);
        } catch (NumberParseException $numberParseException) {
            Craft::error($numberParseException->getMessage(), __METHOD__);
            $isValid = false;
        }

        return $isValid;
    }
}
