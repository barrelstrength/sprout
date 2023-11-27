<?php

namespace BarrelStrength\Sprout\core\relations;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use craft\base\Element;

/**
 * @todo - Remove in Craft 5 in favor of native Element listing field
 */
interface RelationsTableInterface
{
    /**
     * Returns all supported classes or null to support everything
     */
    //public static function getAllowedRelationTypes(ElementInterface $element): array;

    /**
     * Returns an instance of the RelationsTableField to use in a FieldLayout
     */
    public static function getRelationsTableField(Element $element): RelationsTableField;
}
