<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;

/**
 * @todo - Remove in Craft 5 in favor of native Element listing field
 */
interface DataSourceRelationsTableInterface
{
    /**
     * Returns all supported classes or null to support everything
     */
    public function getAllowedDataSourceRelationTypes(): array;

    /**
     * Returns an instance of the RelationsTableField to use in a FieldLayout
     */
    public function getDataSourceRelationsTableField(): RelationsTableField;
}
