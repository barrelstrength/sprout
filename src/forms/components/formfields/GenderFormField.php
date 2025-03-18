<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Html;

class GenderFormField extends Field implements FormFieldInterface
{
    use FormFieldTrait;

    public array $genderOptions = [];

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_REFERENCE);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Gender');
    }

    /**
     * Define database column
     */
    public function defineContentAttribute(): bool
    {
        // field type doesn’t need its own column
        // in the content table, return false
        return false;
    }

    public function selectorIcon(): string
    {
        return 'envelope';
    }

    public function getFieldInputFolder(): string
    {
        return 'gender';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Gender/example',
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
            'errorMessage' => '',
            'renderingOptions' => $renderingOptions,
        ];
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Gender/input',
            [
                'id' => $namespaceInputId,
                'field' => $this,
                'name' => $name,
                'value' => $value,
            ]
        );
    }
}
