<?php

namespace BarrelStrength\Sprout\sitemaps\components\elements;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataInterface;
use craft\elements\Category;
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
 * @property Category $owner
 */
class CategorySitemapMetadataBehavior extends Behavior implements SitemapMetadataInterface
{
    public function getSitemapMetadataTotalElements(): int
    {
        $element = $this->owner;

        $totalElements = $element::find()
            ->groupId($this->owner->groupId)
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
            ->groupId($elementGroupId)
            ->offset($offset)
            ->limit($limit)
            ->site($site)
            ->status($element::STATUS_ENABLED);

        return $elements->all();
    }
}
