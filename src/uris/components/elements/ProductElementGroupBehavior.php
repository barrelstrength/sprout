<?php

namespace BarrelStrength\Sprout\uris\components\elements;

use BarrelStrength\Sprout\uris\elementgroups\ElementGroup;
use BarrelStrength\Sprout\uris\elementgroups\ElementGroupInterface;
use craft\commerce\elements\Product;
use craft\commerce\models\ProductType;
use craft\commerce\services\ProductTypes;
use yii\base\Behavior;

/**
 * Extends Elements with abstracted Element Group behaviors
 *
 * Access directly from the Element:
 * - element.attribute
 * - element.method()
 *
 * @see UrisHelper::attachBehaviors() for initialization
 *
 * @property Product $owner
 */
class ProductElementGroupBehavior extends Behavior implements ElementGroupInterface
{
    public function setElementGroupId(ProductType $elementGroup): void
    {
        $this->owner->typeId = $elementGroup->id;
    }

    /**
     * @return ElementGroup[]
     */
    public function defineElementGroups($siteId): array
    {
        $elementGroups = [];

        $productTypes = new ProductTypes();

        $sections = $productTypes->getAllProductTypes();

        foreach ($sections as $section) {
            $siteSettings = $section->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($siteId == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $elementGroups[] = $section;
                }
            }
        }

        return $elementGroups;
    }
}
