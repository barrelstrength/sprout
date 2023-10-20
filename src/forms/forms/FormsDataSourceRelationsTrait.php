<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use craft\events\RegisterComponentTypesEvent;

trait FormsDataSourceRelationsTrait
{
    public function getAllowedRelationTypes(): array
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

    public function getRelationsTableField(): RelationsTableField
    {
        $reportRows = DataStudioModule::getInstance()->dataSources->getDataSourceRelations($this);

        return new RelationsTableField([
            'attribute' => 'data-source-relations',
            'rows' => $reportRows,
        ]);
    }
}
