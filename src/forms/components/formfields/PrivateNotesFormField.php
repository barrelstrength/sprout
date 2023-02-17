<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fields\PlainText as CraftPlainText;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;
use yii\db\Schema;

class PrivateNotesFormField extends Field implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Private Notes');
    }

    public function defineContentAttribute(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function isPlainInput(): bool
    {
        return true;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/sticky-note.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'privatenotes';
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/PrivateNotes/input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]
        );
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/PrivateNotes/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        // Only visible and updated in the Control Panel
        return TemplateHelper::raw('');
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
        ];
    }
}
