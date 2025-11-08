<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
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

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_LAYOUT);
    }

    public function allowRequired(): bool
    {
        return false;
    }

    public function isEditable(): bool
    {
        return false;
    }

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

    public function selectorIcon(): string
    {
        return 'clipboard';
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

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
        ];
    }
}
