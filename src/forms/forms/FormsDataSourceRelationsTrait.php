<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\components\fieldlayoutelements\DataStudioRelationsTableField;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;

trait FormsDataSourceRelationsTrait
{
    public function getDataSourceRelationTypes(): array
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

    public function getDataSourceRelationsField(): DataStudioRelationsTableField
    {
        $reportRows = DataStudioModule::getInstance()->dataSources->getDataSourceRelations($this);

        return new DataStudioRelationsTableField([
            'attribute' => 'data-source-relations',
            'rows' => $reportRows,
        ]);
    }
}
