<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Tags as CraftTags;
use craft\helpers\Template as TemplateHelper;
use Twig\Markup;

class TagsFormField extends CraftTags implements FormFieldInterface
{
    use FormFieldTrait;

    public string $cssClasses = '';

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/elementfieldsettings';

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/tags.svg';
    }

    public function getFieldInputFolder(): string
    {
        return 'tags';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/Tags/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    {
        $tags = FormsModule::getInstance()->frontEndFields->getFrontEndTags($this->getSettings());

        $rendered = Craft::$app->getView()->renderTemplate('tags/input',
            [
                'name' => $this->handle,
                'value' => $value->ids(),
                'field' => $this,
                'submission' => $submission,
                'renderingOptions' => $renderingOptions,
                'tags' => $tags,
            ]
        );

        return TemplateHelper::raw($rendered);
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftTags::class,
        ];
    }
}
