<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\ElementInterface;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\PlainText;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Db;
use LitEmoji\LitEmoji;

class SingleLineFormField extends PlainText implements FormFieldInterface
{
    use FormFieldTrait;

    public ?string $placeholder = '';

    public ?int $charLimit = null;

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_COMMON);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Single Line');
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value !== null) {
            $value = LitEmoji::shortcodeToUnicode($value);
            $value = trim(preg_replace('#\R#u', "\n", $value));
        }

        return $value !== '' ? $value : null;
    }

    public function selectorIcon(): string
    {
        return 'font';
    }

    public function getFieldInputFolder(): string
    {
        return 'singleline';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/SingleLine/settings',
            [
                'field' => $this,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/SingleLine/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/SingleLine/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        return [
            'name' => $this->handle,
            'placeholder' => $this->placeholder,
            'hasInstructions' => $this->instructions ? true : false,
            'charLimit' => $this->charLimit,
            'value' => $submission->getFrontEndFormFieldValue($this),
            'errors' => $submission->getErrors($this->handle),
        ];
    }

    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        $value = (string)$value;

        return LitEmoji::unicodeToShortcode($value);
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftDropdown::class,
        ];
    }
}
