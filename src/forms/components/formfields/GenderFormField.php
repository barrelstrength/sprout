<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Html;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;

class GenderFormField extends Field implements FormFieldInterface
{
    use FormFieldTrait;

    public array $genderOptions = [];

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

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/envelope.svg';
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

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $rendered = Craft::$app->getView()->renderTemplate('gender/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'submission' => $submission,
                'errorMessage' => '',
                'renderingOptions' => $renderingOptions,
            ]
        );

        return TemplateHelper::raw($rendered);
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
