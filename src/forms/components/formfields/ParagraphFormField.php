<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use Craft;
use craft\base\ElementInterface;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Db;
use LitEmoji\LitEmoji;

class ParagraphFormField extends CraftPlainText implements FormFieldInterface
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
        return Craft::t('sprout-module-forms', 'Paragraph');
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
        return 'paragraph';
    }

    public function getFieldInputFolder(): string
    {
        return 'paragraph';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Paragraph/settings', [
            'field' => $this,
        ]);
    }

    /**
     * Adds support for edit field in the Entries section of SproutForms (Control
     * panel html)
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Paragraph/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Paragraph/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        return [
            'name' => $this->handle,
            'value' => $submission->getFrontEndFormFieldValue($this),
            'renderingOptions' => $renderingOptions,
            'placeholder' => $this->placeholder,
            'maxLength' => $this->charLimit,
            'initialRows' => $this->initialRows,
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
        ];
    }
}
