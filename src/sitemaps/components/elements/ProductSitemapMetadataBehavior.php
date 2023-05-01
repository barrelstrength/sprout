<?php

namespace BarrelStrength\Sprout\sitemaps\components\elements;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataInterface;
use craft\commerce\elements\Product;
use yii\base\Behavior;

/**
 * Extends Elements with Sitemap Metadata behaviors
 *
 * Access directly from the Element:
 * - element.attribute
 * - element.method()
 *
 * @see SitemapModule::attachElementBehaviors() for initialization
 *
 * @property Product $owner
 */
class ProductSitemapMetadataBehavior extends Behavior implements SitemapMetadataInterface
{
    public function getSitemapMetadataTotalElements(): int
    {
        $element = $this->owner;

        $totalElements = $element::find()
            ->typeId($this->owner->typeId)
            ->siteId($this->owner->siteId)
            ->count();

        return (int)$totalElements;
    }

    public function getSitemapMetadataElements(
        $elementGroupId,
        $offset,
        $limit,
        $site
    ): array {
        $element = $this->owner;

        $elements = $element::find()
            ->typeId($elementGroupId)
            ->offset($offset)
            ->limit($limit)
            ->site($site)
            ->status($element::STATUS_LIVE);

        return $elements->all();
    }
}
