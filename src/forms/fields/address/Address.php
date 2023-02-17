<?php

namespace BarrelStrength\Sprout\forms\fields\address;

use BarrelStrength\Sprout\forms\FormsModule;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Craft;
use craft\base\Model;

class Address extends Model
{
    public ?int $id = null;

    public ?int $elementId = null;

    public ?int $siteId = null;

    public ?int $fieldId = null;

    public string $countryCode = 'US';

    public string $countryThreeLetterCode = '';

    public string $currencyCode = '';

    public string $locale = '';

    public string $administrativeArea = '';

    public string $administrativeAreaCode = '';

    public string $locality = '';

    public string $dependentLocality = '';

    public string $postalCode = '';

    public string $sortingCode = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $country = '';

    public function __toString()
    {
        return FormsModule::getInstance()->addressFormatter->getAddressDisplayHtml($this);
    }

    public function init(): void
    {
        // Initialize country-related information based on the country code
        if ($this->countryCode) {
            $countryRepository = new CountryRepository();
            $country = $countryRepository->get($this->countryCode);

            $this->country = $country->getName();
            $this->countryCode = $country->getCountryCode();
            $this->countryThreeLetterCode = $country->getThreeLetterCode();
            $this->currencyCode = $country->getCurrencyCode();
            $this->locale = $country->getLocale();

            $subdivisionRepository = new SubdivisionRepository();
            $subdivision = $subdivisionRepository->get($this->administrativeAreaCode, [$this->countryCode]);

            if ($subdivision !== null) {
                $this->administrativeArea = $subdivision->getName();
            }
        }

        parent::init();
    }

    /**
     * Return the Address HTML for the appropriate region
     */
    public function getAddressDisplayHtml(): ?string
    {
        if (!$this->id) {
            return null;
        }

        return FormsModule::getInstance()->addressFormatter->getAddressDisplayHtml($this);
    }

    public function validatePostalCode($attribute): bool
    {
        $postalCode = $this->{$attribute};

        if ($postalCode === null) {
            return true;
        }

        $addressFormatRepository = new AddressFormatRepository();
        $addressFormat = $addressFormatRepository->get($this->countryCode);

        if ($addressFormat->getPostalCodePattern() !== null) {
            $pattern = $addressFormat->getPostalCodePattern();

            if (preg_match('/^' . $pattern . '$/', $postalCode)) {
                return true;
            }
        }

        $this->addError($attribute, Craft::t('sprout-module-forms', '{postalName} is not a valid.', [
            'postalName' => ucwords($addressFormat->getPostalCodeType()),
        ]));

        return true;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['postalCode', 'validatePostalCode'];
        $rules[] = [
            'address1',
            'required',
            'message' => Craft::t('sprout-module-forms', 'Address 1 field cannot be blank.'),
        ];

        return $rules;
    }
}
