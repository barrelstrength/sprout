<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldHelper;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\Email as CraftEmail;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Html;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;

class EmailFormField extends CraftEmail implements FormFieldInterface
{
    use FormFieldTrait;

    public ?string $customPattern = null;

    public bool $customPatternToggle = false;

    public ?string $customPatternErrorMessage = null;

    public bool $uniqueEmail = false;

    public string $cssClasses = '';

    public ?string $placeholder = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Email');
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Email/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/envelope.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'email';
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Email/settings',
            [
                'field' => $this,
            ]);
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        $fieldContext = FormFieldHelper::getFieldContext($this, $element);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Email/input',
            [
                'namespaceInputId' => $namespaceInputId,
                'id' => $inputId,
                'name' => $name,
                'value' => $value,
                'fieldContext' => $fieldContext,
                'placeholder' => $this->placeholder,
                'element' => $element,
            ]);
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $errorMessage = $this->getErrorMessage();
        $placeholder = $this['placeholder'] ?? '';

        return [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'submission' => $submission,
            'errorMessage' => $errorMessage,
            'renderingOptions' => $renderingOptions,
            'placeholder' => $placeholder,
        ];
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $errorMessage = $this->getErrorMessage();
        $placeholder = $this['placeholder'] ?? '';

        $rendered = Craft::$app->getView()->renderTemplate('email/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'submission' => $submission,
                'errorMessage' => $errorMessage,
                'renderingOptions' => $renderingOptions,
                'placeholder' => $placeholder,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateEmail';

        if ($this->uniqueEmail) {
            $rules[] = 'validateUniqueEmail';
        }

        return $rules;
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     */
    public function validateEmail(ElementInterface $element): void
    {
        $isValid = true;
        $value = $element->getFieldValue($this->handle);
        $customPattern = $this->customPattern;
        $checkPattern = $this->customPatternToggle;

        if ($checkPattern) {
            // Use backtick as delimiters as they are invalid characters for emails
            $customPattern = '`' . $customPattern . '`';

            if (!preg_match($customPattern, $value)) {
                $isValid = false;
            }
        } else {
            $isValid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        if (!$isValid) {
            $message = $this->getErrorMessage();
            $element->addError($this->handle, $message);
        }
    }

    public function validateUniqueEmail(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        $fieldHandle = $element->fieldColumnPrefix . $this->handle;
        $contentTable = $element->contentTable;

        $query = (new Query())
            ->select($fieldHandle)
            ->from($contentTable)
            ->innerJoin(Table::ELEMENTS . ' elements', '[[elements.id]] = ' . $contentTable . '.`elementId`')
            ->where([$fieldHandle => $value])
            ->andWhere(['elements.draftId' => null])
            ->andWhere(['elements.revisionId' => null])
            ->andWhere(['elements.dateDeleted' => null]);

        if ($element->getCanonicalId()) {
            // Exclude current element or source element (if draft) from our results
            $query->andWhere(['not in', 'elementId', $element->getCanonicalId()]);
        }

        $emailExists = $query->scalar();

        $isValid = !$emailExists;

        if (!$isValid) {
            $message = Craft::t('sprout-module-forms', $this->name . ' must be a unique email.');
            $element->addError($this->handle, $message);
        }
    }

    public function getErrorMessage(): string
    {
        if ($this->customPatternToggle && $this->customPatternErrorMessage) {
            return Craft::t('sprout-module-forms', $this->customPatternErrorMessage);
        }

        return Craft::t('sprout-module-forms', $this->name . ' must be a valid email.');
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftEmail::class,
            CraftDropdown::class,
        ];
    }
}
