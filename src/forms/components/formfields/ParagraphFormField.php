<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Db;
use craft\helpers\Template as TemplateHelper;
use LitEmoji\LitEmoji;
use Twig\Markup;
use yii\db\Schema;

class ParagraphFormField extends CraftPlainText implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public ?string $placeholder = '';

    public int $initialRows = 4;

    public ?int $charLimit = null;

    public ?string $columnType = Schema::TYPE_TEXT;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Paragraph');
    }

    public function getContentColumnType(): string
    {
        return $this->columnType;
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
        return '@Sprout/Assets/dist/static/fields/icons/paragraph.svg';
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
            'value' => $value,
            'field' => $this,
            'submission' => $submission,
            'renderingOptions' => $renderingOptions,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('paragraph/input',
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['charLimit'], 'validateCharLimit'];

        return $rules;
    }
}
