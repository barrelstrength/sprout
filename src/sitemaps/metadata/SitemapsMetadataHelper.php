<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\models\Section;
use yii\db\ActiveRecord;

class SitemapsMetadataHelper
{
    /**
     * Get Sitemap Metadata related to this Element Group
     *
     * Order the results by Element Group ID: type-id
     * Example: entries-5, categories-12
     */
    public static function getSitemapMetadataIndexedByElementGroupKey(Element $element, $siteId = null): array
    {
        $sitemapMetadataByType = self::getSitemapMetadataByType($element, $siteId);

        $sitemapMetadata = [];

        foreach ($sitemapMetadataByType as $sitemapMetadataGroup) {
            $elementGroupKey = $element::pluralLowerDisplayName() . '-' . $sitemapMetadataGroup['elementGroupId'];
            $sitemapMetadata[$elementGroupKey] = $sitemapMetadataGroup;
        }

        return $sitemapMetadata;
    }

    public static function getSitemapMetadataByType(Element $element, int $siteId): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[type]]' => $element::class,
                '[[siteId]]' => $siteId,
            ])
            ->all();
    }

    public static function getSitemapMetadataById($id): SitemapMetadataRecord|ActiveRecord|null
    {
        return SitemapMetadataRecord::find()
            ->where([
                'id' => $id,
            ])
            ->one();
    }

    public static function isSinglesSection(Element $element): bool
    {
        if (!$element instanceof Entry) {
            return false;
        }

        return $element->getType()->handle === Section::TYPE_SINGLE;
    }

    public static function hasCustomPages(int $siteId): bool
    {
        // Fetching all Custom Sitemap defined in Sprout SEO
        $customPagesSitemapMetadata = (new Query())
            ->select('id')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $siteId])
            ->andWhere(['type' => SitemapMetadata::NO_ELEMENT_TYPE])
            ->andWhere(['not', ['uri' => null]])
            ->count();

        return $customPagesSitemapMetadata > 0;
    }
}
