<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\sitemaps\SitemapsSettings;
use Craft;
use craft\db\Query;
use craft\helpers\UrlHelper;
use craft\models\Site;
use DateTime;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class CustomPagesSitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, Site $site): void
    {
        if (self::hasCustomPages($site)) {
            $sitemapUrls[] = UrlHelper::siteUrl('sitemap-custom-pages.xml');
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
     * Returns all Custom Section URLs
     */
    public static function getCustomPagesUrls(Site $site): array
    {
        $urls = [];

        // Fetch all Custom Pages
        $sitemapMetadata = (new Query())
            ->select('uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['[[siteId]]' => $site->id])
            ->andWhere(['[[sourceKey]]' => SitemapKey::CUSTOM_PAGES])
            ->all();

        foreach ($sitemapMetadata as $sitemapMetadataGroup) {
            $sitemapMetadataGroup['url'] = null;
            // Adding each custom location indexed by its URL
            if (!UrlHelper::isAbsoluteUrl($sitemapMetadataGroup['uri'])) {
                $sitemapMetadataGroup['url'] = UrlHelper::siteUrl($sitemapMetadataGroup['uri']);
            }

            $modified = new DateTime($sitemapMetadataGroup['dateUpdated']);
            $sitemapMetadataGroup['modified'] = $modified->format('Y-m-d\Th:i:s\Z');

            $urls[$sitemapMetadataGroup['uri']] = $sitemapMetadataGroup;
        }

        return self::getSitemapStructure($urls);
    }

    /**
     * Process Custom Pages Sitemaps for Multi-Lingual Sitemaps that can have custom pages from multiple sections
     */
    public static function getCustomPagesUrlsForMultipleIds($siteIds, $sitesInGroup): array
    {
        $urls = [];

        $sitemapMetadata = (new Query())
            ->select('[[siteId]], uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['in', '[[siteId]]', $siteIds])
            ->andWhere(['[[sourceKey]]' => SitemapKey::CUSTOM_PAGES])
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {

            foreach ($sitemapMetadata as $sitemapMetadataGroup) {
                if ($siteInGroup->id !== (int)$sitemapMetadataGroup['siteId']) {
                    continue;
                }

                $sitemapMetadataGroup['url'] = null;
                // Adding each custom location indexed by its URL

                $url = Craft::getAlias($siteInGroup->baseUrl) . $sitemapMetadataGroup['uri'];
                $sitemapMetadataGroup['url'] = $url;

                $modified = new DateTime($sitemapMetadataGroup['dateUpdated']);
                $sitemapMetadataGroup['modified'] = $modified->format('Y-m-d\Th:i:s\Z');

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
