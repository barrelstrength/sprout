<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use Craft;
use craft\db\Query;
use craft\helpers\UrlHelper;
use craft\models\Site;
use DateTime;

class CustomPagesSitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, array $sites): void
    {
        foreach ($sites as $site) {
            // if we find one site that has custom pages, we add the custom pages sitemap to the sitemap index
            if (self::hasCustomPages($site)) {
                $sitemapUrls[] = UrlHelper::siteUrl('sitemap-custom-pages.xml');

                return;
            }
        }
    }

    public static function getCustomPagesSitemapMetadata(Site $site): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[siteId]]' => $site->id,
                '[[sourceKey]]' => SitemapKey::CUSTOM_PAGES,
            ])
            ->all();
    }

    /**
     * Process Custom Pages Sitemaps for Multi-Lingual Sitemaps that can have custom pages from multiple sections
     */
    public static function getCustomPagesUrls(array $sites): array
    {
        $urls = [];
        $siteIds = array_keys($sites);

        $sitemapMetadata = (new Query())
            ->select('[[siteId]], uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['in', '[[siteId]]', $siteIds])
            ->andWhere(['[[sourceKey]]' => SitemapKey::CUSTOM_PAGES])
            ->all();

        foreach ($sites as $site) {

            foreach ($sitemapMetadata as $sitemapMetadataGroup) {
                if ($site->id !== (int)$sitemapMetadataGroup['siteId']) {
                    continue;
                }

                $sitemapMetadataGroup['url'] = null;

                $url = Craft::getAlias($site->baseUrl) . $sitemapMetadataGroup['uri'];
                $sitemapMetadataGroup['url'] = $url;

                $modified = new DateTime($sitemapMetadataGroup['dateUpdated']);
                $sitemapMetadataGroup['modified'] = $modified->format('Y-m-d\Th:i:s\Z');

                // Adding each custom location indexed by its URL
                $urls[$sitemapMetadataGroup['uri']] = $sitemapMetadataGroup;
            }
        }

        return self::getSitemapStructure($urls);
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

    /**
     * Used for Custom Pages where we localized URLs are managed by where they are stored
     */
    public static function getSitemapStructure(array $urls): array
    {
        $sitemapUrls = [];

        foreach ($urls as $url) {
            $sitemapUrls[] = $url;
        }

        return $sitemapUrls;
    }
}
