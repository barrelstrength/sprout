<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\CustomFormField;
use BarrelStrength\Sprout\forms\forms\Forms;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\events\DefineBehaviorsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class SubmissionsHelper
{
    public static function attachSubmissionElementFieldLayoutBehavior(DefineBehaviorsEvent $event): void
    {
        if ($event->sender->type === SubmissionElement::class) {
            $event->behaviors[SubmissionElementFieldLayoutBehavior::class] = SubmissionElementFieldLayoutBehavior::class;
        }
    }

    public static function getSubmissionFieldLayout(FormElement $form): FieldLayout|SubmissionElementFieldLayoutBehavior
    {
        $layout = null;

        if ($form->submissionFieldLayoutUid) {
            $layout = Craft::$app->getFields()->getLayoutByUid($form->submissionFieldLayoutUid);
        }

        if (!$layout) {
            $layout = new FieldLayout([
                'type' => SubmissionElement::class,
            ]);

            $layoutTab = new FieldLayoutTab();
            $layoutTab->name = Craft::t('sprout-module-forms', 'Page');
            $layoutTab->uid = StringHelper::UUID();
            $layout->setTabs([$layoutTab]);

            $layoutElements = $layoutTab->getElements();

            $layoutTab->setElements($layoutElements);
        }

        $layout->provider = $form;

        return $layout;
    }

    public static function getSubmissionFieldLayoutFromConfig(FormElement $form, array $config = []): FieldLayout|SubmissionElementFieldLayoutBehavior
    {
        $tabConfigs = ArrayHelper::remove($config, 'tabs');

        $layout = new FieldLayout([
                'type' => SubmissionElement::class,
                'provider' => $form,
                'uid' => $form->submissionFieldLayoutUid ?? StringHelper::UUID(),
            ] + $config);

        if (is_array($tabConfigs)) {
            $fieldLayoutTabs = array_map(
                static fn(array $tabConfig) => self::createSubmissionFieldLayoutTabFromConfig($form, $layout, $tabConfig),
                $tabConfigs,
            );
            // Re-index the array
            $tabConfig = array_values($fieldLayoutTabs);
            $layout->setTabs($tabConfig);
        } else {
            $layout->setTabs([]);
        }

        return $layout;
    }

    public static function createSubmissionFieldLayoutTabFromConfig(FormElement $form, FieldLayout $fieldLayout, array $tabConfig): FieldLayoutTab
    {
        $elementConfigs = ArrayHelper::remove($tabConfig, 'elements');

        $tab = new FieldLayoutTab($tabConfig);
        $tab->layout = $fieldLayout;

        $fieldLayoutElements = [];

        foreach ($elementConfigs as $layoutElementConfig) {
            $field = Craft::$app->getFields()->getFieldByUid($layoutElementConfig['fieldUid'] ?? null);
            $fieldConfig = $layoutElementConfig['formField'] ?? null;
            $fieldSettings = $fieldConfig['settings'] ?? [];

            if ($field === null) {
                $fieldType = $fieldConfig['type'];

                unset(
                    $fieldConfig['type'],
                    $fieldConfig['settings'],
                );

                $field = new $fieldType($fieldConfig);
                $field->context = 'sproutForms:' . $form->id;
            }

            $field->setAttributes($fieldSettings, false);

            $label = $layoutElementConfig['formField']['name'] ?? null;
            $instructions = $layoutElementConfig['formField']['instructions'] ?? null;
            $handle = $layoutElementConfig['formField']['handle'] ?? null;

            unset($layoutElementConfig['formField']);

            $fieldLayoutElement = new CustomFormField($field);
            $fieldLayoutElement->layout = $fieldLayout;
            $fieldLayoutElement->required = $layoutElementConfig['required'] === true;
            $fieldLayoutElement->width = $layoutElementConfig['width'];
            $fieldLayoutElement->uid = $layoutElementConfig['uid'];
            $fieldLayoutElement->label = $label;
            $fieldLayoutElement->instructions = $instructions;
            $fieldLayoutElement->handle = $handle;

            $fieldLayoutElements[] = $fieldLayoutElement;
        }

        $tab->setElements($fieldLayoutElements);

        return $tab;
    }

    public static function setFormMetadataSessionVariable(FormElement $form): void
    {
        $settings = FormsModule::getInstance()->getSettings();
        $formType = $form->getFormType();

        $formMetadata = array_merge(
            $settings->formMetadata,
            $formType->formTypeMetadata
        );

        $formMetadataValues = [];

        foreach ($formMetadata as $formMetadatum) {
            $formMetadataValues[$formMetadatum['label']] = Craft::$app->getView()->renderObjectTemplate(
                $formMetadatum['metadatumFormat'],
                Forms::getFormMetadataVariables()
            );
        }

        Craft::$app->getSession()->set('formMetadata:' . $form->id, $formMetadataValues);
    }

    public static function getFormMetadataSessionVariable(int $formId): array
    {
        return Craft::$app->getSession()->get('formMetadata:' . $formId) ?? [];
    }
}
