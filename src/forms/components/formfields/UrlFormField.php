<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldHelper;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\fields\PlainText as CraftPlainText;
use craft\fields\Url as CraftUrl;
use craft\helpers\Html;

class UrlFormField extends CraftUrl implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public ?string $customPatternErrorMessage = null;

    public ?bool $customPatternToggle = null;

    public ?string $customPattern = null;

    public ?string $placeholder = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'URL');
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/chain.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'url';
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Url/settings', [
            'field' => $this,
        ]);
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        $fieldContext = FormFieldHelper::getFieldContext($this, $element);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Url/input', [
                'namespaceInputId' => $namespaceInputId,
                'id' => $inputId,
                'name' => $name,
                'value' => $value,
                'fieldContext' => $fieldContext,
                'placeholder' => $this->placeholder,
                'element' => $element,
            ]
        );
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Url/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $errorMessage = FormsModule::getInstance()->urlField->getErrorMessage($this);
        $placeholder = $this->placeholder ?? '';

        return [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'submission' => $submission,
            'pattern' => $this->customPattern,
            'errorMessage' => $errorMessage,
            'renderingOptions' => $renderingOptions,
            'placeholder' => $placeholder,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $errorMessage = FormsModule::getInstance()->urlField->getErrorMessage($this);
    //    $placeholder = $this->placeholder ?? '';
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('url/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value,
    //            'field' => $this,
    //            'submission' => $submission,
    //            'pattern' => $this->customPattern,
    //            'errorMessage' => $errorMessage,
    //            'renderingOptions' => $renderingOptions,
    //            'placeholder' => $placeholder,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if ($value) {
            return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return '';
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateUrl';

        return $rules;
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     */
    public function validateUrl(ElementInterface $element): void
    {
        $isValid = true;
        $value = $element->getFieldValue($this->handle);
        $customPattern = $this->customPattern;
        $checkPattern = $this->customPatternToggle;

        if ($customPattern && $checkPattern) {
            // Use backtick as delimiters as they are invalid characters for emails
            $customPattern = '`' . $customPattern . '`';

            if (!preg_match($customPattern, $value)) {
                $isValid = false;
            }
        } else {
            $path = parse_url($value, PHP_URL_PATH);
            $encodedPath = array_map('urlencode', explode('/', $path));
            $url = str_replace($path, implode('/', $encodedPath), $value);

            $isValid = filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        if (!$isValid) {
            $message = $this->getErrorMessage();
            $element->addError($this->handle, $message);
        }
    }

    public function getErrorMessage(): string
    {
        if ($this->customPatternToggle && $this->customPatternErrorMessage) {
            return Craft::t('sprout-module-forms', $this->customPatternErrorMessage);
        }

        return Craft::t('sprout-module-forms', $this->name . ' must be a valid URL.');
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftUrl::class,
            self::class,
        ];
    }
}
