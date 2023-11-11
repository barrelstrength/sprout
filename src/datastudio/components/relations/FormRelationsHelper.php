<?php

namespace BarrelStrength\Sprout\datastudio\components\relations;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureSettingsEvent;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureTabsEvent;
use Craft;
use craft\base\Element;
use craft\events\CreateFieldLayoutFormEvent;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\FieldLayoutTab;
use yii\db\Expression;

class FormRelationsHelper implements RelationsTableInterface
{
    public static function addDataSourceFormTypeSettings(RegisterFormFeatureSettingsEvent $event): void
    {
        $event->featureSettings['enableReports'] = [
            'label' => Craft::t('sprout-module-data-studio', 'Enable Reports'),
            'settings' => Html::tag('div', 'Reports HTML'),
        ];
    }

    public static function addDataSourceRelationsTab(RegisterFormFeatureTabsEvent $event): void
    {
        $element = $event->element ?? null;

        if (!$element instanceof FormElement) {
            return;
        }

        $formType = $element->getFormType();
        $featureSettings = $formType->featureSettings['enableReports'] ?? [];
        $enableTab = $featureSettings['enabled'] ?? false;

        if (!$enableTab) {
            return;
        }

        $fieldLayout = $event->fieldLayout;

        Craft::$app->getView()->registerJs('new DataSourceRelationsTable(' . $element->id . ', ' . $element->siteId . ');');

        $reportsTab = new FieldLayoutTab();
        $reportsTab->layout = $fieldLayout;
        $reportsTab->name = Craft::t('sprout-module-data-studio', 'Reports');
        $reportsTab->uid = 'SPROUT-UID-FORMS-REPORTS-TAB';
        $reportsTab->sortOrder = 70;
        $reportsTab->setElements([
            self::getRelationsTableField($element),
        ]);

        $event->tabs[] = $reportsTab;
    }

    public static function getRelationsTableField(Element $element): RelationsTableField
    {
        $reportRows = self::getDataSourceRelations($element);

        $dataSourceTypes = DataStudioModule::getInstance()->dataSources->getDataSourceRelationsTypes();

        $options = TemplateHelper::optionsFromComponentTypes($dataSourceTypes);

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-data-studio', 'New Data Set...'),
                'value' => '',
            ],
        ];

        foreach ($options as $option) {
            $optionValues[] = $option;
        }

        $createSelect = Cp::selectHtml([
            'id' => 'new-data-set',
            'name' => 'type',
            'options' => $optionValues,
            'value' => '',
        ]);

        $sidebarMessage = Craft::t('sprout-module-data-studio', 'This page lists any data sets that are known to be related to this form. Manage all your reporting via Data Studio.');
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage), [
            'class' => 'meta read-only',
        ]);

        return new RelationsTableField([
            'attribute' => 'data-source-relations',
            'rows' => $reportRows,
            'newButtonHtml' => $createSelect,
            'sidebarHtml' => $sidebarHtml,
        ]);
    }

    public static function getDataSourceRelations(Element $element): array
    {
        $dataSourceTypes = DataStudioModule::getInstance()->dataSources->getDataSourceRelationsTypes();

        $query = DataSetElement::find()
            ->orderBy('sprout_datasets.name')
            ->where(['in', 'sprout_datasets.type', $dataSourceTypes]);

        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_datasets.settings, "formId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_datasets.settings, "$.formId")');
        }

        $query->andWhere(['=', $expression, $element->id]);

        $rows = array_map(static function($element) {
            return [
                'elementId' => $element->id,
                'name' => $element->name,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element->getDataSource()::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $query->all());

        return $rows;
    }
}
