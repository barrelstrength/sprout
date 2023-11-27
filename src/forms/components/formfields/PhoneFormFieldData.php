<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use Craft;
use craft\base\Model;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneFormFieldData extends Model
{
    public string $phone;

    public string $country;

    protected ?string $code = null;

    protected ?string $international = null;

    protected ?string $national = null;

    protected ?string $E164 = null;

    protected ?string $RFC3966 = null;

    public function __toString()
    {
        if (!$this->international) {
            $this->populatePhoneDetails();
        }

        return (string)$this->international;
    }

    public function getCode(): ?string
    {
        if (!$this->code) {
            $this->populatePhoneDetails();
        }

        return $this->code;
    }

    public function getInternational(): ?string
    {
        if (!$this->international) {
            $this->populatePhoneDetails();
        }

        return $this->international;
    }

    public function getNational(): ?string
    {
        if (!$this->national) {
            $this->populatePhoneDetails();
        }

        return $this->national;
    }

    public function getE164(): ?string
    {
        if (!$this->E164) {
            $this->populatePhoneDetails();
        }

        return $this->E164;
    }

    public function getRFC3966(): ?string
    {
        if (!$this->RFC3966) {
            $this->populatePhoneDetails();
        }

        return $this->RFC3966;
    }

    /**
     * Populate the model with specific details based on the phone number and country
     */
    public function populatePhoneDetails(): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($this->phone, $this->country);

            if (!$phoneNumber) {
                throw new NumberParseException(400, 'Unable to parse phone number.');
            }

            $this->code = $phoneUtil->getCountryCodeForRegion($this->country);
            $this->international = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
            $this->national = $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
            $this->E164 = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            $this->RFC3966 = $phoneUtil->format($phoneNumber, PhoneNumberFormat::RFC3966);
        } catch (NumberParseException $numberParseException) {
            // Log it and continue
            Craft::error($numberParseException->getMessage(), __METHOD__);
        }
    }
}
