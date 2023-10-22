<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomQueryDataSource;
use BarrelStrength\Sprout\datastudio\datasets\DataSetHelper;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use Craft;

trait FormsDataSourceRelationsTrait
{
    public function getAllowedDataSourceRelationTypes(): array
    {
        $dataSourceTypes = [
            SubmissionsDataSource::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $dataSourceTypes,
        ]);

        /** @var FormElement $this */
        $this->trigger(FormElement::EVENT_REGISTER_DATA_SOURCE_RELATIONS_TYPES, $event);

        return $event->types;
    }

    public function getDataSourceRelationsTableField(): RelationsTableField
    {
        $reportRows = DataStudioModule::getInstance()->dataSources->getDataSourceRelations($this);

        //$site = Cp::requestedSite();

        $dataSourceTypes = $this->getAllowedDataSourceRelationTypes() ?? DataStudioModule::getInstance()->dataSources->getDataSourceTypes();

        $options = TemplateHelper::optionsFromComponentTypes($dataSourceTypes);

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-forms', 'New Data Set...'),
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

        $sidebarMessage = Craft::t('sprout-module-forms', 'This page lists any data sets that are known to be related to this form. Manage all your reporting via Data Studio.');
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage) , [
            'class' => 'meta read-only',
        ]);

        return new RelationsTableField([
            'attribute' => 'data-source-relations',
            'rows' => $reportRows,
            'newButtonHtml' => $createSelect,
            'sidebarHtml' => $sidebarHtml,
        ]);
    }

    public function getAllowedDataSourceTypes(): array
    {

    }
}
