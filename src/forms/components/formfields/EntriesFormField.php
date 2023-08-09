<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Entries as CraftEntries;

class EntriesFormField extends CraftEntries implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/elementfieldsettings';

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/newspaper-o.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'entries';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Entries/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $entries = FormsModule::getInstance()->frontEndFields->getFrontEndEntries($this->getSettings());
        $multiple = $this->maxRelations === null || $this->maxRelations > 1;

        return [
            'name' => $this->handle,
            'value' => $value->ids(),
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
            'entries' => $entries,
            'multiple' => $multiple,
            'selectionLabel' => $this->selectionLabel,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $entries = FormsModule::getInstance()->frontEndFields->getFrontEndEntries($this->getSettings());
    //
    //    $rendered = Craft::$app->getView()->renderTemplate('entries/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value->ids(),
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //            'entries' => $entries,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftEntries::class,
        ];
    }
}
