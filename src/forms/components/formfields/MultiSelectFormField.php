<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\fields\MultiSelect as CraftMultiSelect;

class MultiSelectFormField extends CraftMultiSelect implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_COMMON);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Multi Select');
    }

    public function selectorIcon(): string
    {
        return 'bars';
    }

    public function getFieldInputFolder(): string
    {
        return 'multiselect';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/MultiSelect/example',
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
            'renderingOptions' => $renderingOptions,
            'options' => $this->options,
        ];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftMultiSelect::class,
        ];
    }
}
