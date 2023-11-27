<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\Checkboxes as CraftCheckboxes;
use craft\fields\conditions\OptionsFieldConditionRule;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\Lightswitch as CraftLightswitch;
use craft\fields\PlainText as CraftPlainText;
use craft\fields\RadioButtons as CraftRadioButtons;
use craft\helpers\Html;

class OptInFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public ?string $cssClasses = null;

    public ?string $optInMessage = null;

    public bool $selectedByDefault = false;

    public ?string $optInValueWhenTrue = null;

    public ?string $optInValueWhenFalse = null;

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_REFERENCE);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Opt-in');
    }

    public function init(): void
    {
        if ($this->optInMessage === null) {
            $this->optInMessage = Craft::t('sprout-module-forms', 'Agree to terms?');
        }

        if ($this->optInValueWhenTrue === null) {
            $this->optInValueWhenTrue = Craft::t('sprout-module-forms', 'Yes');
        }

        if ($this->optInValueWhenFalse === null) {
            $this->optInValueWhenFalse = Craft::t('sprout-module-forms', 'No');
        }

        parent::init();
    }

    public function displayLabel(): bool
    {
        return false;
    }

    public function displayInstructionsField(): bool
    {
        return false;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/check-square.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'optin';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/OptIn/settings',
            [
                'field' => $this,
            ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/OptIn/input',
            [
                'name' => $this->handle,
                'namespaceInputId' => $namespaceInputId,
                'label' => $this->optInMessage,
                'value' => 1,
                'checked' => $value,
            ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/OptIn/example',
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
            'label' => $this->optInMessage,
            'selectedByDefault' => $this->selectedByDefault,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('optin/input',
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

    public function getElementConditionRuleType(): array|string|null
    {
        return OptionsFieldConditionRule::class;
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftDropdown::class,
            CraftCheckboxes::class,
            CraftRadioButtons::class,
            CraftLightswitch::class,
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['optInMessage'], 'required'];

        return $rules;
    }
}
