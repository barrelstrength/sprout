<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldHelper;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\conditions\TextFieldConditionRule;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Html;

class RegularExpressionFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public string $customPatternErrorMessage = '';

    public string $customPattern = '';

    public string $placeholder = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Regex');
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/puzzle-piece.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'regularexpression';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/RegularExpression/settings', [
            'field' => $this,
        ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        $fieldContext = FormFieldHelper::getFieldContext($this, $element);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/RegularExpression/input', [
            'id' => $namespaceInputId,
            'field' => $this,
            'name' => $name,
            'value' => $value,
            'fieldContext' => $fieldContext,
        ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/RegularExpression/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $placeholder = $this->placeholder ?? '';

        $pattern = $this->customPattern;

        return [
            'name' => $this->handle,
            'value' => $value,
            //'field' => $this,
            //'submission' => $submission,
            'pattern' => $pattern,
            'errorMessage' => $this->customPatternErrorMessage,
            'renderingOptions' => $renderingOptions,
            'placeholder' => $placeholder,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $placeholder = $this->placeholder ?? '';
    //
    //    $pattern = $this->customPattern;
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('regularexpression/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value,
    //            'field' => $this,
    //            'submission' => $submission,
    //            'pattern' => $pattern,
    //            'errorMessage' => $this->customPatternErrorMessage,
    //            'renderingOptions' => $renderingOptions,
    //            'placeholder' => $placeholder,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateRegularExpression';

        return $rules;
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     */
    public function validateRegularExpression(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        $customPattern = $this->customPattern;

        $isValid = true;

        if (!empty($customPattern)) {
            // Use backtick as delimiters
            $customPattern = '`' . $customPattern . '`';

            if (!preg_match($customPattern, $value)) {
                $isValid = false;
            }
        }

        if (!$isValid) {
            $element->addError($this->handle,
                $this->getErrorMessage()
            );
        }
    }

    public function getErrorMessage(): string
    {
        if ($this->customPattern && $this->customPatternErrorMessage) {
            return Craft::t('sprout-module-forms', $this->customPatternErrorMessage);
        }

        return Craft::t('sprout-module-forms', $this->name . ' must be a valid pattern.');
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return TextFieldConditionRule::class;
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            self::class,
        ];
    }
}
