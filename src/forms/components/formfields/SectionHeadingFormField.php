<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use barrelstrength\sprout\web\assetbundles\quill\QuillAsset;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Html;
use craft\helpers\Template as TemplateHelper;
use ReflectionClass;
use Twig\Markup;
use yii\db\Schema;

class SectionHeadingFormField extends Field implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    public string $notes = '';

    public bool $hideLabel = false;

    public string $output = '';

    public function allowRequired(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Section Heading');
    }

    public static function hasContentColumn(): bool
    {
        return false;
    }

    public function isPlainInput(): bool
    {
        return true;
    }

    public function defineContentAttribute(): string
    {
        return Schema::TYPE_STRING;
    }

    public function displayInstructionsField(): bool
    {
        return false;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/header.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'sectionheading';
    }

    public function getSettingsHtml(): ?string
    {
        $reflect = new ReflectionClass($this);
        $name = $reflect->getShortName();

        $inputId = Html::id($name);
        $view = Craft::$app->getView();
        $namespaceInputId = $view->namespaceInputId($inputId);

        //        @todo - throws error, does not exist yet.
        //        $view->registerAssetBundle(QuillAsset::class);

        $options = [
            'richText' => 'Rich Text',
            'markdown' => 'Markdown',
            'html' => 'HTML',
        ];

        return $view->renderTemplate('sprout-module-forms/_components/fields/SectionHeading/settings',
            [
                'id' => $namespaceInputId,
                'name' => $name,
                'field' => $this,
                'outputOptions' => $options,
            ]
        );
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $name = $this->handle;
        $inputId = Html::id($name);
        $namespaceInputId = Craft::$app->getView()->namespaceInputId($inputId);

        if ($this->notes === null) {
            $this->notes = '';
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/SectionHeading/input',
            [
                'id' => $namespaceInputId,
                'name' => $name,
                'field' => $this,
            ]
        );
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/SectionHeading/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $name = $this->handle;
        $namespaceInputId = $this->getNamespace() . '-' . $name;

        if ($this->notes === null) {
            $this->notes = '';
        }

        $rendered = Craft::$app->getView()->renderTemplate('sectionheading/input',
            [
                'id' => $namespaceInputId,
                'field' => $this,
            ]
        );

        return TemplateHelper::raw($rendered);
    }
}
