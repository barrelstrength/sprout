<?php

namespace BarrelStrength\Sprout\forms\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class FormBuilderField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'form-builder-field';

    protected function useFieldset(): bool
    {
        return true;
    }

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-forms', 'Form Builder');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof FormElement) {
            return '';
        }

        //        $condition = new UserCondition();
        //        $condition->mainTag = 'div';
        //        $condition->name = 'condition';
        //        $condition->id = 'condition';
        //        $conditionHtml = Cp::fieldHtml($condition->getBuilderHtml(), [
        //            'label' => Craft::t('app', 'Test Condition'),
        //        ]);
        //
        //        $slideoutTabs = '<div id="tab-settings-slideout" class="hidden">
        //        <form method="post" class="fld-element-settings">
        //            <div class="fld-element-settings-body">
        //                <div class="fields">'.$conditionHtml.'</div>
        //            </div>
        //        </form>
        //    </div>';
        //
        //        Craft::$app->getView()->registerHtml($slideoutTabs);

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/forms/_formbuilder/fieldLayout', [
            'form' => $element,
        ]);
    }
}
