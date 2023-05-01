<?php

namespace BarrelStrength\Sprout\sitemaps\components\elements;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataInterface;
use craft\elements\Entry;
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
 * @property Entry $owner
 */
class EntrySitemapMetadataBehavior extends Behavior implements SitemapMetadataInterface
{
    public function getSitemapMetadataTotalElements(): int
    {
        $element = $this->owner;

        $totalElements = $element::find()
            ->sectionId($this->owner->sectionId)
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
            ->sectionId($elementGroupId)
            ->offset($offset)
            ->limit($limit)
            ->site($site)
            ->status($element::STATUS_LIVE);

        return $elements->all();
    }
}
