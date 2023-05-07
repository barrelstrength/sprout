<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use craft\db\Query;
use craft\elements\Entry;
use craft\models\Section;

class SitemapsMetadataHelper
{
    public static function isSinglesSection(SitemapMetadataRecord $sitemapMetadata): bool
    {
        $element = new $sitemapMetadata->type();

        if (!$element instanceof Entry) {
            return false;
        }
        
        $sectionId = SourceKeyHelper::findElementGroupId($sitemapMetadata->sourceKey);

        if ($sectionId > 0) {
            $element->sectionId = $sectionId;
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
