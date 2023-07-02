<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\models\Section;
use craft\models\Site;

class SitemapsMetadataHelper
{
    public const UUID_PATTERN = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    public static function getSitemapMetadataByUid($sitemapMetadataUid, Site $site): array
    {
        $query = SitemapMetadataRecord::find()
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['uid' => $sitemapMetadataUid]);

        // Return single item as array
        return $query->all();
    }

    public static function getSinglesSitemapMetadata(Site $site): array
    {
        $singlesUids = (new Query())
            ->select('uid')
            ->from(Table::SECTIONS)
            ->where(['type' => Section::TYPE_SINGLE])
            ->column();

        return SitemapMetadataRecord::find()
            ->select(['*'])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['in', 'sourceKey', $singlesUids])
            ->all();
    }

    public static function getCustomQuerySitemapMetadata(Site $site): array
    {
        // Fetching all Custom Sitemap defined in Sprout SEO
        $customQuerySitemapMetadata = (new Query())
            ->select('*')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['sourceKey' => SitemapKey::CUSTOM_QUERY])
            ->indexBy('uid')
            ->all();

        $sitemapMetadata = [];

        foreach ($customQuerySitemapMetadata as $uid => $metadata) {
            $sitemapMetadata[$uid] = new SitemapMetadataRecord($metadata);
        }

        return $sitemapMetadata;
    }

    public static function isSinglesSection(SitemapMetadataRecord $sitemapMetadata): bool
    {
        if ($sitemapMetadata->type !== Entry::class) {
            return false;
        }

        $sectionType = (new Query())
            ->select('type')
            ->from(Table::SECTIONS)
            ->where(['uid' => $sitemapMetadata->sourceKey])
            ->scalar();

        return $sectionType === Section::TYPE_SINGLE;
    }

    public static function hasCustomPages(Site $site): bool
    {
        // Fetching all Custom Sitemap defined in Sprout SEO
        $customPagesSitemapMetadata = (new Query())
            ->select('id')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['[[sourceKey]]' => SitemapKey::CUSTOM_PAGES])
            ->andWhere(['not', ['uri' => null]])
            ->count();

        return $customPagesSitemapMetadata > 0;
    }
}
