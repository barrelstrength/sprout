<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\PreviewableFieldInterface;
use craft\fields\Addresses as CraftAddressesField;

class AddressFormField extends CraftAddressesField implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_REFERENCE);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Address');
    }

    public function hasMultipleLabels(): bool
    {
        return true;
    }

    public function selectorIcon(): string
    {
        return 'location-dot';
    }

    public function getFieldInputFolder(): string
    {
        return 'address';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Address/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        return [
            'name' => $this->handle,
            'value' => $submission->getFrontEndFormFieldValue($this, $value->ids()),
            'renderingOptions' => $renderingOptions,
            'countryInputHtml' => '',
            'addressFormHtml' => '',
        ];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftAddressesField::class,
        ];
    }
}
