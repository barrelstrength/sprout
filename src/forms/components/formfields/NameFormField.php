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
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;

class NameFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public bool $displayMultipleFields = false;

    public bool $displayMiddleName = false;

    public bool $displayPrefix = false;

    public bool $displaySuffix = false;

    private string|bool $hasMultipleLabels = false;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Name');
    }

    public function hasMultipleLabels(): bool
    {
        return $this->hasMultipleLabels;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/user.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'name';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Name/settings', [
            'field' => $this,
        ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        $fieldContext = FormFieldHelper::getFieldContext($this, $element);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Name/input', [
            'namespaceInputId' => $namespaceInputId,
            'id' => $inputId,
            'name' => $name,
            'field' => $this,
            'value' => $value,
            'fieldContext' => $fieldContext,
        ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Name/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        if ($this->displayMultipleFields) {
            $this->hasMultipleLabels = true;
        }

        $rendered = Craft::$app->getView()->renderTemplate('name/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'submission' => $submission,
                'renderingOptions' => $renderingOptions,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    /**
     * Prepare our Name for use as an NameFormFieldData
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $nameFormFieldData = new NameFormFieldData();

        // String value when retrieved from db
        if (is_string($value)) {
            $nameArray = Json::decode($value);
            $nameFormFieldData->setAttributes($nameArray, false);

            return $nameFormFieldData;
        }

        // Array value from post data
        if (is_array($value) && isset($value['name'])) {

            $nameFormFieldData->setAttributes($value['name'], false);

            if ($fullNameShort = $value['name']['fullNameShort'] ?? null) {
                $nameArray = explode(' ', trim($fullNameShort));
                $nameFormFieldData->firstName = $nameArray[0] ?? $fullNameShort;
                unset($nameArray[0]);

                $nameFormFieldData->lastName = implode(' ', $nameArray);
            }

            return $nameFormFieldData;
        }

        return $value;
    }

    /**
     * Prepare the field value for the database.
     *
     * We store the Name as JSON in the content column.
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): ?string
    {
        if ($value === null) {
            return null;
        }

        // Submitting an Element to be saved
        if ($value instanceof NameFormFieldData) {
            if (!$value->getFullNameExtended()) {
                return null;
            }

            return Json::encode($value->getAttributes());
        }

        return $value;
    }

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if ($value) {
            /** @var NameFormFieldData $value */
            return $value->getFullName();
        }

        return '';
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
