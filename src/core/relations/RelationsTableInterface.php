<?php

namespace BarrelStrength\Sprout\core\relations;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;

/**
 * @todo - Remove in Craft 5 in favor of native Element listing field
 */
interface RelationsTableInterface
{
    /**
     * Returns all supported classes or null to support everything
     */
    public function getAllowedRelationTypes(): array;

    /**
     * Returns an instance of the RelationsTableField to use in a FieldLayout
     */
    public function getRelationsTableField(): RelationsTableField;
}
