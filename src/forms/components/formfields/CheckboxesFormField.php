<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\fields\Checkboxes as CraftCheckboxes;
use craft\fields\Checkboxes as CraftCheckboxesField;

class CheckboxesFormField extends CraftCheckboxesField implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public function hasMultipleLabels(): bool
    {
        return true;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/check-square.svg';
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
            'value' => $value,
            'field' => $this,
            'submission' => $submission,
            'renderingOptions' => $renderingOptions,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('checkboxes/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value,
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftCheckboxes::class,
        ];
    }
}
