<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\forms\Forms;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\fields\conditions\TextFieldConditionRule;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\PlainText as CraftPlainText;
use Exception;

class HiddenFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public bool $allowEdits = false;

    public ?string $value = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Hidden');
    }

    public function isPlainInput(): bool
    {
        return true;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/user-secret.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'hidden';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Hidden/settings',
            [
                'field' => $this,
            ]);
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Hidden/input',
            [
                'id' => $this->handle,
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
            ]);
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Hidden/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        if ($this->value) {
            try {
                $value = Craft::$app->view->renderObjectTemplate($this->value, Forms::getFieldVariables());
            } catch (Exception $exception) {
                Craft::error($exception->getMessage(), __METHOD__);
            }
        }

        return [
            'name' => $this->handle,
            'value' => $value,
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    if ($this->value) {
    //        try {
    //            $value = Craft::$app->view->renderObjectTemplate($this->value, Forms::getFieldVariables());
    //        } catch (Exception $exception) {
    //            Craft::error($exception->getMessage(), __METHOD__);
    //        }
    //    }
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('hidden/input',
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
        return TextFieldConditionRule::class;
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftPlainText::class,
            CraftDropdown::class,
        ];
    }
}
