<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\fields\Checkboxes as CraftCheckboxes;
use craft\fields\Checkboxes as CraftCheckboxesField;

class CheckboxesFormField extends CraftCheckboxesField implements FormFieldInterface
{
    use FormFieldTrait;

    protected static bool $optionIcons = false;

    protected static bool $optionColors = false;

    protected static bool $allowCustomOptions = false;

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_COMMON);
    }

    public function selectorIcon(): string
    {
        return 'square-check';
    }

    public function hasMultipleLabels(): bool
    {
        return true;
    }

    public function getFieldInputFolder(): string
    {
        return 'checkboxes';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Checkboxes/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        return [
            'name' => $this->handle,
            'value' => $submission->getFrontEndFormFieldValue($this),
            'renderingOptions' => $renderingOptions,

            'options' => $this->options,
        ];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftCheckboxes::class,
        ];
    }
}
