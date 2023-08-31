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
use yii\db\Schema;

class SingleLineFormField extends PlainText implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public ?string $placeholder = '';

    public ?int $charLimit = null;

    public ?string $columnType = Schema::TYPE_TEXT;

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

    /**
     * Validates that the Character Limit isn't set to something higher than the Column Type will hold.
     */
    public function validateCharLimit(string $attribute): void
    {
        if ($this->charLimit) {
            $columnTypeMax = Db::getTextualColumnStorageCapacity($this->columnType);

            if ($columnTypeMax && $columnTypeMax < $this->charLimit) {
                $this->addError($attribute, Craft::t('sprout-module-forms', 'Character Limit is too big for your chosen Column Type.'));
            }
        }
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/font.svg';
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

    public function getContentColumnType(): string
    {
        return $this->columnType;
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
            'value' => $submission->getFieldValue($this->handle),
            'errors' => $submission->getErrors($this->handle),
        ];
    }


    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('singleline/input',
    //        $this->getFrontEndInputVariables($value, $submission, $renderingOptions)
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['charLimit'], 'validateCharLimit'];

        return $rules;
    }
}
