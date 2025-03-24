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
        return [];
        //$categories = FormsModule::getInstance()->frontEndFields->getFrontEndCategories($this->getSettings());
        //$multiple = $this->maxRelations === null || $this->maxRelations > 1;
        //
        //return [
        //    'name' => $this->handle,
        //    'value' => $value->ids(),
        //    //'field' => $this,
        //    //'submission' => $submission,
        //    'renderingOptions' => $renderingOptions,
        //    'categories' => $categories,
        //    'multiple' => $multiple,
        //    'selectionLabel' => $this->selectionLabel,
        //];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftAddressesField::class,
        ];
    }
}
