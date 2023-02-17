<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\fields\address\Address as AddressModel;
use BarrelStrength\Sprout\forms\fields\address\Addresses as AddressService;
use BarrelStrength\Sprout\forms\fields\address\AddressFieldTrait;
use BarrelStrength\Sprout\forms\fields\address\CountryRepositoryHelper;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use CommerceGuys\Addressing\Address as CommerceGuysAddress;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;

class AddressFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;
    use AddressFieldTrait;

    public string $cssClasses = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Address');
    }

    public static function hasContentColumn(): bool
    {
        return false;
    }

    public function hasMultipleLabels(): bool
    {
        return true;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/map-marker-alt.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'address';
    }

    public function getSettingsHtml(): ?string
    {
        $countryRepositoryHelper = new CountryRepositoryHelper();
        $addressingAvailableLocales = $countryRepositoryHelper->getAvailableLocales();

        $craftAvailableLocales = [];

        foreach (Craft::$app->getI18n()->getAllLocales() as $locale) {
            $craftAvailableLocales[$locale->id] = Craft::t('sprout-module-forms', '{id} – {name}', [
                'name' => $locale->getDisplayName(Craft::$app->language),
                'id' => $locale->id,
            ]);
        }

        $availableLocales = [];

        foreach ($craftAvailableLocales as $localeId => $localeName) {
            if (in_array($localeId, $addressingAvailableLocales, true)) {
                $availableLocales[$localeId] = $localeName;
            }
        }

        if ($this->defaultLanguage === null) {
            $this->defaultLanguage = AddressService::DEFAULT_LANGUAGE;

            // If the primary site language is available choose it as a default language.
            $primarySiteLocaleId = Craft::$app->getSites()->getPrimarySite()->language;
            if (isset($availableLocales[$primarySiteLocaleId])) {
                $this->defaultLanguage = $primarySiteLocaleId;
            }
        }

        // Countries
        if ($this->defaultCountry === null) {
            $this->defaultCountry = AddressService::DEFAULT_COUNTRY;
        }

        $countryRepository = new CountryRepository();
        $countries = $countryRepository->getList($this->defaultLanguage);

        if (is_countable($this->highlightCountries) ? count($this->highlightCountries) : 0) {
            $highlightCountries = FormsModule::getInstance()->addressFormatter->getHighlightCountries($this->highlightCountries);
            $countries = array_merge($highlightCountries, $countries);
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Address/settings', [
                'field' => $this,
                'countries' => $countries,
                'languages' => $availableLocales,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;

        $inputId = Html::id($name);
        $namespaceInputName = Craft::$app->getView()->namespaceInputName($inputId);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        $settings = $this->getSettings();

        $defaultLanguage = $settings['defaultLanguage'] ?? AddressService::DEFAULT_LANGUAGE;
        $defaultCountryCode = $settings['defaultCountry'] ?? AddressService::DEFAULT_COUNTRY;
        $showCountryDropdown = $settings['showCountryDropdown'] ?? null;

        $addressId = null;

        if (is_object($value)) {
            $addressId = $value->id;
        } elseif (is_array($value)) {
            $addressId = $value['id'];
        }

        $addressModel = null;

        if ($element !== null) {
            $addressModel = FormsModule::getInstance()->addressField->getAddressFromElement($element, $this->id);
        }

        if ($addressModel === null) {
            $addressModel = new AddressModel();
            $addressModel->countryCode = $defaultCountryCode;
            $addressModel->fieldId = $this->id;
        }

        $showAddressOnInitialLoad = false;

        // Retain values if element validation fails
        if ($value) {
            $showAddressOnInitialLoad = true;

            $addressModel->id = $value['id'];
            $addressModel->elementId = $value['elementId'];
            $addressModel->siteId = $value['siteId'];
            $addressModel->fieldId = $value['fieldId'];
            $addressModel->countryCode = $value['countryCode'];
            $addressModel->administrativeAreaCode = $value['administrativeAreaCode'];
            $addressModel->locality = $value['locality'];
            $addressModel->dependentLocality = $value['dependentLocality'];
            $addressModel->postalCode = $value['postalCode'];
            $addressModel->sortingCode = $value['sortingCode'];
            $addressModel->address1 = $value['address1'];
            $addressModel->address2 = $value['address2'];
        }

        // Override the Default Country Code with the current country code if it exists
        $defaultCountryCode = $addressModel->countryCode ?? $defaultCountryCode;

        $addressFormatter = FormsModule::getInstance()->addressFormatter;
        $addressFormatter->setNamespace($name);
        $addressFormatter->setLanguage($defaultLanguage);
        $addressFormatter->setCountryCode($defaultCountryCode);
        $addressFormatter->setAddressModel($addressModel);

        if (is_countable($this->highlightCountries) ? count($this->highlightCountries) : 0) {
            $addressFormatter->setHighlightCountries($this->highlightCountries);
        }

        $addressDisplayHtml = $addressId || $showAddressOnInitialLoad
            ? $addressFormatter->getAddressDisplayHtml($addressModel)
            : '';
        $countryInputHtml = $addressFormatter->getCountryInputHtml($showCountryDropdown);
        $addressFormHtml = $addressFormatter->getAddressFormHtml();

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Address/input', [
                'namespaceInputId' => $namespaceInputId,
                'namespaceInputName' => $namespaceInputName,
                'field' => $this,
                'fieldId' => $addressModel->fieldId ?? $field->id ?? null,
                'addressId' => $addressId,
                'defaultCountryCode' => $defaultCountryCode,
                'addressDisplayHtml' => Template::raw($addressDisplayHtml),
                'countryInputHtml' => Template::raw($countryInputHtml),
                'addressFormHtml' => Template::raw($addressFormHtml),
                'showCountryDropdown' => $showCountryDropdown,
                'showAddressOnInitialLoad' => $showAddressOnInitialLoad,
            ]
        );
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): ?AddressModel
    {
        if ($value instanceof AddressModel) {
            return $value;
        }

        if (!$element instanceof ElementInterface) {
            return null;
        }

        // Mark this address for deletion. This is processed in the saveAddress method
        $delete = $value['delete'] ?? 0;
        $deleteAddress = $delete;

        $address = FormsModule::getInstance()->addressField->getAddressFromElement($element, $this->id);

        if ($deleteAddress) {
            // Use the ID from the Address found in the database because the posted Address ID may not
            $this->setDeletedAddressId($address->id ?? null);
        }

        // Add the address field array from the POST data to the Address Model address
        if (is_array($value)) {

            if ($address instanceof AddressModel) {
                $address->id = $value['id'] ?? null;
            } else {
                $address = new AddressModel();
            }

            $address->setAttributes($value, false);

            $address->elementId = $element->id;
            $address->siteId = $element->siteId;
            $address->fieldId = $this->id;
        }

        return $address;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        if ($element->duplicateOf !== null) {
            FormsModule::getInstance()->addressField->duplicateAddress($this, $element, $isNew);
        } else {
            FormsModule::getInstance()->addressField->saveAddress($this, $element, $isNew);
        }

        // Reset the field value if this is a new element
        if ($element->duplicateOf || $isNew) {
            $element->setFieldValue($this->handle, null);
        }

        parent::afterElementSave($element, $isNew);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Address/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $name = $this->handle;
        $settings = $this->getSettings();

        $countryCode = $settings['defaultCountry'] ?? $this->defaultCountry;
        $showCountryDropdown = $settings['showCountryDropdown'] ?? 0;

        $addressModel = new AddressModel();

        // This defaults to Sprout Base and we need it to get updated to look
        // in the Sprout Forms Form Template location like other fields.
        FormsModule::getInstance()->addressFormatter->setBaseAddressFieldPath('');

        FormsModule::getInstance()->addressFormatter->setNamespace($name);

        if (isset($this->highlightCountries) && count($this->highlightCountries)) {
            FormsModule::getInstance()->addressFormatter->setHighlightCountries($this->highlightCountries);
        }

        FormsModule::getInstance()->addressFormatter->setCountryCode($countryCode);
        FormsModule::getInstance()->addressFormatter->setAddressModel($addressModel);
        FormsModule::getInstance()->addressFormatter->setLanguage($this->defaultLanguage);

        if ($this->highlightCountries !== []) {
            FormsModule::getInstance()->addressFormatter->setHighlightCountries($this->highlightCountries);
        }

        $countryInputHtml = FormsModule::getInstance()->addressFormatter->getCountryInputHtml($showCountryDropdown);
        $addressFormHtml = FormsModule::getInstance()->addressFormatter->getAddressFormHtml();

        $rendered = Craft::$app->getView()->renderTemplate('address/input', [
                'field' => $this,
                'submission' => $submission,
                'name' => $this->handle,
                'renderingOptions' => $renderingOptions,
                'addressFormHtml' => TemplateHelper::raw($addressFormHtml),
                'countryInputHtml' => TemplateHelper::raw($countryInputHtml),
                'showCountryDropdown' => $showCountryDropdown,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }

        $addressFormatRepository = new AddressFormatRepository();
        $countryRepository = new CountryRepository();
        $subdivisionRepository = new SubdivisionRepository();
        $formatter = new DefaultFormatter($addressFormatRepository, $countryRepository, $subdivisionRepository);

        $address = new CommerceGuysAddress();
        $address = $address
            ->withCountryCode($value->countryCode)
            ->withAdministrativeArea($value->administrativeAreaCode)
            ->withLocality($value->locality)
            ->withPostalCode($value->postalCode)
            ->withAddressLine1($value->address1)
            ->withAddressLine2($value->address2);

        $html = $formatter->format($address);

        return str_replace(' ', '&nbsp;', $html);
    }

    public function getElementValidationRules(): array
    {
        return ['validateAddress'];
    }

    public function validateAddress(ElementInterface $element): bool
    {
        if (!$this->required) {
            return true;
        }

        $values = $element->getFieldValue($this->handle);

        $addressModel = new AddressModel($values);
        $addressModel->validate();

        if ($addressModel->hasErrors()) {
            $errors = $addressModel->getErrors();

            if ($errors) {
                foreach ($errors as $error) {
                    $firstMessage = $error[0] ?? null;
                    $element->addError($this->handle, $firstMessage);
                }
            }
        }

        return true;
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            self::class,
        ];
    }
}
