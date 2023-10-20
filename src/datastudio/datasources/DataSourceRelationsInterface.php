<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\components\fieldlayoutelements\DataStudioRelationsTableField;
use craft\base\SavableComponentInterface;

interface DataSourceRelationsInterface
{
    /**
     * Returns all supported Data Source classes or null to support everything
     */
    public function getDataSourceRelationTypes(): array;

    /**
     * Returns an instance of the DataStudioRelationsTableField to use in a FieldLayout
     */
    public function getDataSourceRelationsField(): DataStudioRelationsTableField;
}
