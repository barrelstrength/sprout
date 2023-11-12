<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\sitemaps\SitemapsSettings;
use Craft;
use craft\helpers\UrlHelper;
use craft\models\Site;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class SitemapsMetadataHelper
{
    public static function getSitemapMetadataByUid($sitemapMetadataUid, Site $site): ?SitemapMetadataRecord
    {
        /** @var SitemapMetadataRecord $record */
        $record = SitemapMetadataRecord::find()
            ->where(['uid' => $sitemapMetadataUid])
            ->andWhere(['siteId' => $site->id])
            ->one();

        return $record;
    }

    public static function getXmlSitemapMetadataByUid($sitemapMetadataUid, Site $site): ?SitemapMetadataRecord
    {
        /** @var SitemapMetadataRecord $record */
        $record = SitemapMetadataRecord::find()
            ->where(['uid' => $sitemapMetadataUid])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['enabled' => true])
            ->one();

        return $record;
    }

    public static function getPaginatedSitemapUrls(array &$sitemapIndexPages, SitemapMetadataRecord $sitemapMetadata, $totalElements): void
    {
        $totalElementsPerSitemap = self::getTotalElementsPerSitemap();
        $totalSitemaps = ceil($totalElements / $totalElementsPerSitemap);

        $debugString = self::getDebugString($sitemapMetadata);

        // Build Sitemap Index URLs
        for ($i = 1; $i <= $totalSitemaps; $i++) {
            $sitemapIndexUrl = UrlHelper::siteUrl() . 'sitemap-' . $sitemapMetadata->uid . '-' . $i . '.xml' . $debugString;

            $sitemapIndexPages[] = $sitemapIndexUrl;
        }
    }

    public static function isValidSitemapRequest(array $siteIds, Site $site): void
    {
        if (empty($siteIds)) {
            throw new NotFoundHttpException('XML Sitemap not enabled for this site.');
        }

        $settings = SitemapsModule::getInstance()->getSettings();
        $aggregationMethodMultiLingual = $settings->sitemapAggregationMethod === SitemapsSettings::AGGREGATION_METHOD_MULTI_LINGUAL;

        if (Craft::$app->getIsMultiSite() && $aggregationMethodMultiLingual) {

            // get first item in $sitesInGroup array unknown key
            $firstSiteIdInGroup = reset($siteIds) ?: null;

            // Only render sitemaps for the primary site in a group
            if ($site->id !== $firstSiteIdInGroup) {
                throw new NotFoundHttpException('Unable to find XML Sitemap for first site in group.');
            }
        }
    }

    /**
     * Returns all sites to process for the current sitemap request
     * If only one site is found, it is also returned as an array
     */
    public static function getSitemapSites(Site $site): array
    {
        $settings = SitemapsModule::getInstance()->getSettings();

        $isMultisite = Craft::$app->getIsMultiSite();
        $aggregationMethodMultiLingual = $settings->sitemapAggregationMethod === SitemapsSettings::AGGREGATION_METHOD_MULTI_LINGUAL;

        // For multi-lingual sitemaps, get all sites in the Current Site group
        if ($isMultisite && $aggregationMethodMultiLingual && in_array($site->groupId, $settings->getEnabledGroupIds(), false)) {
            $sitesInGroup = Craft::$app->getSites()->getSitesByGroupId($site->groupId);

            // update keys to be the siteId
            return array_combine(array_column($sitesInGroup, 'id'), $sitesInGroup);
        }

        // For non-multi-lingual sitemaps, get the current site
        if (!$aggregationMethodMultiLingual && in_array($site->id, array_filter($settings->getEnabledSiteIds()), false)) {
            return [
                $site->id => $site,
            ];
        }

        return [];
    }

    public static function getEditableSiteIds(): array
    {
        $settings = SitemapsModule::getInstance()->getSettings();
        $isMultiSite = Craft::$app->getIsMultiSite();

        $isAggregationMethodMultiLanguage =
            $settings->sitemapAggregationMethod === SitemapsSettings::AGGREGATION_METHOD_MULTI_LINGUAL;

        $enabledSiteIds = $settings->getEnabledSiteIds();
        $enabledSiteGroupIds = $settings->getEnabledGroupIds();

        $missingSettingsScenario1 = !$isAggregationMethodMultiLanguage && empty($enabledSiteIds);

        $missingSettingsScenario2 = $isMultiSite
            && !$isAggregationMethodMultiLanguage
            && empty($enabledSiteGroupIds);

        if ($missingSettingsScenario1 && $missingSettingsScenario2) {
            throw new NotFoundHttpException('No Sites are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site for your Sitemap.');
        }

        $missingSettingsScenario3 = $isMultiSite
            && $isAggregationMethodMultiLanguage
            && empty($enabledSiteGroupIds);

        if ($missingSettingsScenario3) {
            throw new NotFoundHttpException('No Site Groups are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site Group for your Sitemap.');
        }

        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // For per-site sitemaps, only display the Sites enabled in the Sprout SEO settings
        if ($isAggregationMethodMultiLanguage) {
            $siteIdsFromEditableGroups = [];

            foreach ($enabledSiteGroupIds as $enabledSiteGroupId) {
                $enabledSitesInGroup = Craft::$app->sites->getSitesByGroupId($enabledSiteGroupId);

                foreach ($enabledSitesInGroup as $enabledSite) {
                    $siteIdsFromEditableGroups[$enabledSite->uid] = $enabledSite->id;
                }
            }

            $editableSiteIds = array_intersect($siteIdsFromEditableGroups, $editableSiteIds);
        } else {
            $editableSiteIds = array_intersect($enabledSiteIds, $editableSiteIds);
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        // The array keys of our editableSiteIds are their UIDs
        foreach (array_keys($editableSiteIds) as $key => $siteUid) {
            if (!$currentUser->can('editSite:' . $siteUid)) {
                unset($editableSiteIds[$key]);
            }
        }

        if (empty($editableSiteIds)) {
            throw new ForbiddenHttpException('User not permitted to edit sitemaps for any sites.');
        }

        return $editableSiteIds;
    }

    public static function getFirstSiteInGroup(Site $site): Site
    {
        $isMultiSite = Craft::$app->getIsMultiSite();

        if ($isMultiSite) {
            // For Multi-Site we have to figure out which Site and Site Group matter
            $currentSiteGroup = Craft::$app->sites->getGroupById($site->groupId);

            if (!$currentSiteGroup) {
                throw new NotFoundHttpException('Site group not found.');
            }

            $sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);

            if (empty($sitesInCurrentSiteGroup)) {
                throw new NotFoundHttpException('No Sites found in group.');
            }

            $firstSiteInGroup = reset($sitesInCurrentSiteGroup);

            return $firstSiteInGroup;
        }

        return $site;
    }

    public static function getTotalElementsPerSitemap(int $total = 500): int
    {
        $settings = SitemapsModule::getInstance()->getSettings();

        return $settings->totalElementsPerSitemap ?? $total;
    }

    public static function getDebugString(mixed $sitemapMetadata): string
    {
        if (Craft::$app->config->getGeneral()->devMode) {
            $debugString =
                '?siteId=' . $sitemapMetadata->siteId
                . '&sitemapMetadataId=' . $sitemapMetadata->id
                . '&type=' . $sitemapMetadata->type;
        }

        return $debugString ?? '';
    }
}
