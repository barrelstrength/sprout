<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\EmailDropdownHelper;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\fields\data\SingleOptionFieldData;
use craft\fields\Dropdown as CraftDropdownField;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\StringHelper;
use yii\db\Schema;

class EmailDropdownFormField extends CraftDropdownField implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Email Dropdown');
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/share.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'emaildropdown';
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        // Make the unobfuscated values available to email notifications
        if ($value && Craft::$app->request->getIsSiteRequest() && Craft::$app->getRequest()->getIsPost()) {
            // Swap our obfuscated number value (e.g. 1) with the email value
            $selectedOption = $this->options[$value];
            $value = $selectedOption['value'];
        }

        return parent::normalizeValue($value, $element);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (Craft::$app->getRequest()->isSiteRequest && $value->selected) {
            // Default fist position.
            $pos = $value->value ?: 0;

            if (isset($this->options[$pos])) {
                return $this->options[$pos]['value'];
            }
        }

        return $value;
    }

    public function getSettingsHtml(): ?string
    {
        $options = $this->options;

        if (!$options) {
            $options = [['label' => '', 'value' => '']];
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/EmailDropdown/settings', [
            'label' => $this->optionsSettingLabel(),
            'field' => $this,
            'options' => $options,
        ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var SingleOptionFieldData $value */
        $valueOptions = $value->getOptions();
        $anySelected = EmailDropdownHelper::isAnyOptionsSelected(
            $valueOptions,
            $value->value
        );

        $name = $this->handle;
        $value = $value->value;

        if (!$anySelected) {
            $value = $this->defaultValue();
        }

        $options = $this->options;

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/EmailDropdown/input',
            [
                'name' => $name,
                'value' => $value,
                'options' => $options,
            ]
        );
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/EmailDropdown/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $selectedValue = $value->value ?? null;

        $options = $this->options;
        $options = EmailDropdownHelper::obfuscateEmailAddresses($options, $selectedValue);

        return [
            'name' => $this->handle,
            'value' => $value,
            'options' => $options,
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $selectedValue = $value->value ?? null;
    //
    //    $options = $this->options;
    //    $options = EmailDropdownHelper::obfuscateEmailAddresses($options, $selectedValue);
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('emaildropdown/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value,
    //            'options' => $options,
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        $html = '';

        if ($value) {
            $html = $value->label . ': <a href="mailto:' . $value . '" target="_blank">' . $value . '</a>';
        }

        return $html;
    }

    public function getElementValidationRules(): array
    {
        return ['validateEmailDropdown'];
    }

    /**
     * Validates our fields submitted value beyond the checks
     * that were assumed based on the content attribute.
     */
    public function validateEmailDropdown(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle)->value;

        $invalidEmails = [];

        $emailString = $this->options[$value]->value ?? null;

        if ($emailString) {

            $emailAddresses = StringHelper::split($emailString);
            $emailAddresses = array_unique($emailAddresses);

            foreach ($emailAddresses as $emailAddress) {
                if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                    $invalidEmails[] = Craft::t('sprout-module-forms', 'Email does not validate: ' . $emailAddress);
                }
            }
        }

        foreach ($invalidEmails as $invalidEmail) {
            $element->addError($this->handle, $invalidEmail);
        }
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftDropdownField::class,
        ];
    }
}
