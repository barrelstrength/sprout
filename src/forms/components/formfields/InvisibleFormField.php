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
use craft\helpers\Html;
use craft\helpers\Template as TemplateHelper;
use Exception;
use Twig\Markup;

class InvisibleFormField extends Field implements FormFieldInterface, PreviewableFieldInterface
{
    use FormFieldTrait;

    public bool $allowEdits = false;

    public bool $hideValue = false;

    public ?string $value = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Invisible');
    }

    public function isPlainInput(): bool
    {
        return true;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/eye-slash.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'invisible';
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Invisible/settings',
            [
                'field' => $this,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Invisible/input',
            [
                'id' => $namespaceInputId,
                'name' => $name,
                'value' => $value,
                'field' => $this,
            ]
        );
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Invisible/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $this->preProcessInvisibleValue();

        $html = Html::hiddenInput($this->handle);

        return TemplateHelper::raw($html);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            $invisibleValue = Craft::$app->getSession()->get($this->handle);

            // If we have have a value stored in the session for the Invisible Field, use it
            if ($invisibleValue) {
                $value = $invisibleValue;
            }

            // Clean up so the session value doesn't persist
            Craft::$app->getSession()->set($this->handle, null);
        }

        return parent::normalizeValue($value, $element);
    }

    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        $hiddenValue = '';

        if ($value !== '' && $value !== null) {
            $hiddenValue = $this->hideValue ? '&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;' : $value;
        }

        return $hiddenValue;
    }

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

    private function preProcessInvisibleValue(): string
    {
        $value = '';

        if ($this->value) {
            try {
                $value = Craft::$app->view->renderObjectTemplate($this->value, Forms::getFieldVariables());
                Craft::$app->getSession()->set($this->handle, $value);
            } catch (Exception $exception) {
                Craft::error($exception->getMessage(), __METHOD__);
            }
        }

        return $value;
    }
}
