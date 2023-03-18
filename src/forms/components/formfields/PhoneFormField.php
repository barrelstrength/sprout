<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\fields\address\Addresses;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\PhoneHelper;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\conditions\TextFieldConditionRule;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Twig\Markup;

class PhoneFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public ?string $customPatternErrorMessage = null;

    public ?bool $limitToSingleCountry = null;

    public ?string $country = 'US';

    public ?string $placeholder = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Phone');
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/phone.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'phone';
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): ?PhoneFormFieldData
    {
        if ($value instanceof PhoneFormFieldData) {
            return $value;
        }

        if (!$element instanceof ElementInterface) {
            return null;
        }

        $phoneArray = [];

        if (is_string($value)) {
            $phoneArray = Json::decode($value);
        }

        if (is_array($value)) {
            $phoneArray = $value;
        }

        if (isset($phoneArray['phone'], $phoneArray['country'])) {
            $phoneFormFieldData = new PhoneFormFieldData();
            $phoneFormFieldData->country = $phoneArray['country'];
            $phoneFormFieldData->phone = $phoneArray['phone'];

            return $phoneFormFieldData;
        }

        return $value;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhoneFormFieldData) {
            // Don't save anything unless we can render a phone
            if ($value->getNational() === null) {
                return null;
            }

            return Json::encode([
                'country' => $value->country,
                'phone' => $value->phone,
            ]);
        }

        return $value;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Phone/settings', [
            'field' => $this,
        ]);
    }

    public function getExampleInputHtml(): string
    {
        $countries = PhoneHelper::getCountries();

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Phone/example',
            [
                'field' => $this,
                'countries' => $countries,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $countryId = Html::id($name . '-country');
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);
        $namespaceCountryId = Craft::$app->getView()->namespaceInputId($countryId);
        $countries = PhoneHelper::getCountries();

        $country = $value['country'] ?? $this->country;
        $val = $value['phone'] ?? null;

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Phone/input', [
            'namespaceInputId' => $namespaceInputId,
            'namespaceCountryId' => $namespaceCountryId,
            'id' => $inputId,
            'countryId' => $countryId,
            'name' => $this->handle,
            'field' => $this,
            'value' => $val,
            'countries' => $countries,
            'country' => $country,
        ]);
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $name = $this->handle;
        $country = $value['country'] ?? $this->country;
        $countries = PhoneHelper::getCountries();
        $val = $value['phone'] ?? null;

        $rendered = Craft::$app->getView()->renderTemplate('phone/input',
            [
                'name' => $name,
                'value' => $val,
                'field' => $this,
                'submission' => $submission,
                'country' => $country,
                'countries' => $countries,
                'renderingOptions' => $renderingOptions,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        $html = '';

        if ($value->international) {
            $fullNumber = $value->international;
            $html = '<a href="tel:' . $fullNumber . '" target="_blank">' . $fullNumber . '</a>';
        }

        return $html;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validatePhone';

        return $rules;
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     */
    public function validatePhone(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        // Don't run validation if a field is not required and has no value for the phone number
        if (!$this->required && empty($value->phone)) {
            return;
        }

        $phone = $value['phone'] ?? null;
        $country = $value['country'] ?? Addresses::DEFAULT_COUNTRY;

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

        if (!$isValid) {
            $message = $this->getErrorMessage($value->country);
            $element->addError($this->handle, $message);
        }
    }

    public function getErrorMessage($country = null): string
    {
        // Change empty condition to show default message when toggle settings is unchecked
        if ($this->customPatternErrorMessage) {
            return Craft::t('sprout-module-forms', $this->customPatternErrorMessage);
        }

        if (!$country) {
            return Craft::t('sprout-module-forms', 'Country is required.');
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        $exampleNumber = $phoneUtil->getExampleNumber($country);

        if (!$exampleNumber) {
            return Craft::t('sprout-module-forms', '{fieldName} is invalid.', [
                'fieldName' => $this->name,
            ]);
        }

        $exampleNationalNumber = $phoneUtil->format($exampleNumber, PhoneNumberFormat::NATIONAL);

        return Craft::t('sprout-module-forms', '{fieldName} is invalid. Example format: {exampleNumber}', [
            'fieldName' => $this->name,
            'exampleNumber' => $exampleNationalNumber,
        ]);
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return TextFieldConditionRule::class;
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            self::class,
        ];
    }
}
