<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use yii\db\Schema;

class CustomHtmlFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public string $customHtml = '';

    public bool $hideLabel = false;

    public function allowRequired(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Custom HTML');
    }

    public function isPlainInput(): bool
    {
        return true;
    }

    public function displayInstructionsField(): bool
    {
        return false;
    }

    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/code.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'customhtml';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/CustomHtml/settings',
            [
                'field' => $this,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/CustomHtml/input',
            [
                'id' => $this->handle,
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/CustomHtml/example',
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
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
            'customHtml' => $this->customHtml,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('customhtml/input',
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
}
