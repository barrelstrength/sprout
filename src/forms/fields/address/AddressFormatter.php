<?php

namespace BarrelStrength\Sprout\forms\fields\address;

use BarrelStrength\Sprout\forms\fields\address\Address as AddressModel;
use CommerceGuys\Addressing\Address as AddressingAddress;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Craft;

class AddressFormatter
{
    /**
     * @var AddressFormat
     */
    protected AddressFormat $addressFormat;

    /**
     * @var SubdivisionRepository
     */
    protected SubdivisionRepository $subdivisionRepository;

    /**
     * Namespace is set dynamically to the field handle of the Address Field
     * being generated.
     *
     * Defaults to 'address' for use in plugins like Sprout SEO.
     */
    protected string $namespace = 'address';

    protected Address $addressModel;

    protected string $countryCode = 'US';

    protected string $language = 'en';

    protected array $highlightCountries = [];

    /**
     * Our base address field path defaults to the path we use for rendering the Address Field in the
     * Control Panel. In the case of Sprout Forms, we need to override this and set this to blank because
     * Sprout Forms dynamically determines the path so that users can control template overrides.
     */
    private string $baseAddressFieldPath = 'sprout-module-forms/_components/fields/';

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * Format common countries setting values with country names
     */
    public function getHighlightCountries($highlightCountries = []): array
    {
        $countryRepository = new CountryRepository();
        $options = [];

        $commonCountries = $highlightCountries;

        if (!(is_countable($commonCountries) ? count($commonCountries) : 0)) {
            return $options;
        }

        foreach ($commonCountries as $code) {
            $options[$code] = $countryRepository->get($code)->getName();
        }

        return $options;
    }

    public function setHighlightCountries(array $highlightCountries = []): void
    {
        $highlightCountries = $this->getHighlightCountries($highlightCountries);

        $this->highlightCountries = $highlightCountries;
    }

    public function defaultCountryCode(): string
    {
        return 'US';
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $name): void
    {
        $this->namespace = $name;
    }

    public function getAddressModel(): AddressModel
    {
        return $this->addressModel ?? new AddressModel();
    }

    public function setAddressModel(AddressModel $addressModel = null): void
    {
        $this->addressModel = $addressModel ?? null;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode($countryCode = null): void
    {
        $this->countryCode = $countryCode ?? $this->defaultCountryCode();
    }

    public function getBaseAddressFieldPath(): string
    {
        return $this->baseAddressFieldPath;
    }

    public function setBaseAddressFieldPath(string $baseAddressFieldPath): void
    {
        $this->baseAddressFieldPath = $baseAddressFieldPath;
    }

    /**
     * Returns a formatted address to display
     */
    public function getAddressDisplayHtml(AddressModel $model): string
    {
        $address = new AddressingAddress();
        $addressFormatRepository = new AddressFormatRepository();
        $countryRepository = new CountryRepository();
        $subdivisionRepository = new SubdivisionRepository();

        $formatter = new DefaultFormatter($addressFormatRepository, $countryRepository, $subdivisionRepository);

        $address = $address
            ->withCountryCode($model->countryCode)
            ->withAdministrativeArea($model->administrativeAreaCode)
            ->withLocality($model->locality)
            ->withPostalCode($model->postalCode)
            ->withAddressLine1($model->address1)
            ->withAddressLine2($model->address2);

        if ($model->dependentLocality !== null) {
            $address->withDependentLocality($model->dependentLocality);
        }

        return $formatter->format($address) ?? '';
    }

    /**
     * Returns all input fields necessary for a user submit an address
     */
    public function getAddressFormHtml(): string
    {
        $this->subdivisionRepository = new SubdivisionRepository();
        $addressFormatRepository = new AddressFormatRepository();
        $this->addressFormat = $addressFormatRepository->get($this->countryCode);

        $addressLayout = $this->addressFormat->getFormat();

        // Remove unused attributes
        $addressLayout = preg_replace('#%recipient#', '', $addressLayout);
        $addressLayout = preg_replace('#%organization#', '', $addressLayout);
        $addressLayout = preg_replace('#%givenName#', '', $addressLayout);
        $addressLayout = preg_replace('#%familyName#', '', $addressLayout);

        $countryRepository = new CountryRepository();
        $countries = $countryRepository->getList($this->language);

        $countryName = $countries[$this->countryCode];
        if ($countryName) {
            $addressLayout = preg_replace('/' . $countryName . '/', '', $addressLayout);
        }

        // Remove dash on format
        $addressLayout = str_replace('-', '', $addressLayout);

        // Insert line break based on the format
        //$format = nl2br($format);

        // An exception when building our Form Input Field in the CP
        // Removes a backslash character that is needed for the Address Display Only
        if ($this->countryCode === 'TR') {
            $addressLayout = preg_replace('#%locality/#', '%locality', $addressLayout);
        }

        // A few exceptions when building our Form Input Fields for the CP
        // Removes a hardcoded locality that is needed for the Address Display Only
        // These are added automatically in the AddressFormat for front-end display
        if ($this->countryCode === 'AX') {
            $addressLayout = str_replace('ÅLAND', '', $addressLayout);
        }

        if ($this->countryCode === 'GI') {
            $addressLayout = str_replace('GIBRALTAR', '', $addressLayout);
        }

        if ($this->countryCode === 'JE') {
            $addressLayout = str_replace('JERSEY', '', $addressLayout);
        }

        // More whitespace
        $addressLayout = preg_replace('#,#', '', $addressLayout);

        $addressLayout = preg_replace('#%addressLine1#', $this->getAddressLineInputHtml('address1'), $addressLayout);
        $addressLayout = preg_replace('#%addressLine2#', $this->getAddressLineInputHtml('address2'), $addressLayout);
        $addressLayout = preg_replace('#%dependentLocality#', $this->getDependentLocalityInputHtml(), $addressLayout);
        $addressLayout = preg_replace('#%locality#', $this->getLocalityInputHtml(), $addressLayout);
        $addressLayout = preg_replace('#%administrativeArea#', $this->getAdministrativeAreaInputHtml(), $addressLayout);
        $addressLayout = preg_replace('#%postalCode#', $this->getPostalCodeInputHtml(), $addressLayout);

        if (preg_match('#%sortingCode#', $addressLayout)) {
            $addressLayout = preg_replace('#%sortingCode#', $this->getSortingCodeInputHtml(), $addressLayout);
        }

        $addressLayout .= Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/hidden.twig', [
            'class' => 'sprout-address-delete',
            'name' => $this->namespace,
            'inputName' => 'delete',
            'value' => null,
        ]);

        $addressLayout .= Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/hidden.twig', [
            'class' => 'sprout-address-field-id',
            'name' => $this->namespace,
            'inputName' => 'fieldId',
            'value' => $this->getAddressModel()->fieldId,
        ]);

        $addressLayout .= Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/hidden.twig', [
            'class' => 'sprout-address-id',
            'name' => $this->namespace,
            'inputName' => 'id',
            'value' => $this->getAddressModel()->id,
        ]);

        return $addressLayout;
    }

    public function getCountryInputHtml(bool $showCountryDropdown = true): string
    {
        $countryRepository = new CountryRepository();
        $countries = $countryRepository->getList($this->language);

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/select-country.twig', [
                'fieldClass' => 'sprout-address-country-select',
                'label' => $this->renderAddressLabel('Country'),
                'name' => $this->namespace,
                'inputName' => 'countryCode',
                'autocomplete' => 'country',
                'options' => $countries,
                'value' => $this->countryCode ?? $this->defaultCountryCode(),
                'hideDropdown' => !$showCountryDropdown,
                'highlightCountries' => $this->highlightCountries,
            ]
        );
    }

    public function getPostalCodeInputHtml(): string
    {
        $value = $this->getAddressModel()->postalCode;

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $this->renderAddressLabel($this->addressFormat->getPostalCodeType()),
                'name' => $this->namespace,
                'inputName' => 'postalCode',
                'autocomplete' => 'postal-code',
                'value' => $value,
            ]
        );
    }

    protected function renderAddressLabel($label): ?string
    {
        return Craft::t('sprout-module-forms', str_replace('_', ' ', ucwords($label)));
    }

    private function getAddressLineInputHtml($addressName): string
    {
        $value = $this->getAddressModel()->{$addressName};

        $label = $this->renderAddressLabel('Address 1');
        $autocomplete = 'address-line1';

        if ($addressName === 'address2') {
            $label = $this->renderAddressLabel('Address 2');
            $autocomplete = 'address-line2';
        }

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $label,
                'name' => $this->namespace,
                'inputName' => $addressName,
                'autocomplete' => $autocomplete,
                'value' => $value,
            ]
        );
    }

    private function getSortingCodeInputHtml(): string
    {
        $value = $this->getAddressModel()->sortingCode;

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $this->renderAddressLabel('Sorting Code'),
                'name' => $this->namespace,
                'inputName' => 'sortingCode',
                'autocomplete' => 'address-level4',
                'value' => $value,
            ]
        );
    }

    private function getLocalityInputHtml(): string
    {
        $value = $this->getAddressModel()->locality;

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $this->renderAddressLabel($this->addressFormat->getLocalityType()),
                'name' => $this->namespace,
                'inputName' => 'locality',
                'autocomplete' => 'address-level2',
                'value' => $value,
            ]
        );
    }

    private function getDependentLocalityInputHtml(): string
    {
        $value = $this->getAddressModel()->dependentLocality;

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $this->renderAddressLabel($this->addressFormat->getDependentLocalityType()),
                'name' => $this->namespace,
                'inputName' => 'dependentLocality',
                'autocomplete' => 'address-level3',
                'value' => $value,
            ]
        );
    }

    private function getAdministrativeAreaInputHtml(): string
    {
        $value = $this->getAddressModel()->administrativeAreaCode;

        $states = $this->subdivisionRepository->getList([$this->countryCode], $this->language);

        if (!empty($states)) {
            return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/select.twig', [
                    'fieldClass' => 'sprout-address-onchange-country',
                    'label' => $this->renderAddressLabel($this->addressFormat->getAdministrativeAreaType()),
                    'name' => $this->namespace,
                    'inputName' => 'administrativeAreaCode',
                    'autocomplete' => 'address-level1',
                    'options' => $states,
                    'value' => $value,
                ]
            );
        }

        return Craft::$app->view->renderTemplate($this->getBaseAddressFieldPath() . 'address/_components/text.twig', [
                'fieldClass' => 'sprout-address-onchange-country',
                'label' => $this->renderAddressLabel($this->addressFormat->getAdministrativeAreaType()),
                'name' => $this->namespace,
                'inputName' => 'administrativeAreaCode',
                'autocomplete' => 'address-level1',
                'value' => $value,
            ]
        );
    }
}
