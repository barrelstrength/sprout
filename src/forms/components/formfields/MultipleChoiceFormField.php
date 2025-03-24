<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\fields\RadioButtons as CraftRadioButtons;

class MultipleChoiceFormField extends CraftRadioButtons implements FormFieldInterface
{
    use FormFieldTrait;

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_COMMON);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Multiple Choice');
    }

    public function hasMultipleLabels(): bool
    {
        return true;
    }

    public function selectorIcon(): string
    {
        return 'circle-dot';
    }

    public function getFieldInputFolder(): string
    {
        return 'multiplechoice';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/MultipleChoice/example',
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
            CraftRadioButtons::class,
        ];
    }

    protected function optionsSettingLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Multiple Choice Options');
    }
}
