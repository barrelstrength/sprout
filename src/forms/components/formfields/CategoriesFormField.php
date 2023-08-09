<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Categories as CraftCategories;

class CategoriesFormField extends CraftCategories implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/Categories/settings';

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/folder-open.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'categories';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Categories/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $categories = FormsModule::getInstance()->frontEndFields->getFrontEndCategories($this->getSettings());
        $multiple = $this->maxRelations === null || $this->maxRelations > 1;

        return [
            'name' => $this->handle,
            'value' => $value->ids(),
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
            'categories' => $categories,
            'multiple' => $multiple,
            'selectionLabel' => $this->selectionLabel,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $categories = FormsModule::getInstance()->frontEndFields->getFrontEndCategories($this->getSettings());
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('categories/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value->ids(),
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //            'categories' => $categories,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftCategories::class,
        ];
    }
}
