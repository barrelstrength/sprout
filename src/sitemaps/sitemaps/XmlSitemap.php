<?php

namespace BarrelStrength\Sprout\sitemaps\sitemaps;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\sitemaps\SitemapsSettings;
use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use DateTime;
use yii\base\Component;

class XmlSitemap extends Component
{
    /**
     * Prepares sitemaps for a sitemapindex
     */
    public function getSitemapIndex(Site $site): array
    {
        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $sitemapIndexPages = [];
        $hasSingles = false;

        $totalElementsPerSitemap = $this->getTotalElementsPerSitemap();

        $elementsWithUris = $sitemapsService->getElementWithUris();

        $contentSitemapMetadata = $sitemapsService->getContentSitemapMetadata($site);

        foreach ($elementsWithUris as $elementWithUri) {

            foreach ($contentSitemapMetadata as $sitemapMetadata) {
                if (!$sitemapMetadata->enabled) {
                    continue;
                }

                if ($sitemapMetadata->type !== $elementWithUri::class) {
                    continue;
                }

                $elementQuery = $sitemapMetadata->getElementQuery();
                $totalElements = $elementQuery->count();

                // Is this a Singles Section?
                $isSingle = SitemapsMetadataHelper::isSinglesSection($sitemapMetadata);

                // Make sure we don't add Singles more than once
                if ($isSingle && $hasSingles) {
                    continue;
                }

                if ($isSingle) {
                    $hasSingles = true;

                    // Add the singles at the beginning of our sitemap
                    array_unshift(
                        $sitemapIndexPages,
                        UrlHelper::siteUrl() . 'sitemap-singles.xml'
                    );
                } else {
                    $totalSitemaps = ceil($totalElements / $totalElementsPerSitemap);

                    $devMode = Craft::$app->config->getGeneral()->devMode;
                    $debugString = '';

                    if ($devMode) {
                        $debugString =
                            '?siteId=' . $sitemapMetadata->siteId
                            . '&sitemapMetadataId=' . $sitemapMetadata->id
                            . '&type=' . $sitemapMetadata->type;
                    }

                    // Build Sitemap Index URLs
                    for ($i = 1; $i <= $totalSitemaps; $i++) {
                        $sitemapIndexUrl = UrlHelper::siteUrl() . 'sitemap-' . $sitemapMetadata->uid . '-' . $i . '.xml' . $debugString;

                        $sitemapIndexPages[] = $sitemapIndexUrl;
                    }
                }
            }
        }

        if ($customQuerySitemapMetadata = SitemapsMetadataHelper::getCustomQuerySitemapMetadata($site)) {
            foreach ($customQuerySitemapMetadata as $customQuery) {

                $currentConditionRules = Json::decodeIfJson($customQuery['settings']);
                $currentCondition = Craft::$app->conditions->createCondition($currentConditionRules);
                $currentCondition->elementType = Entry::class;

                $query = $currentCondition->elementType::find();
                $currentCondition->modifyQuery($query);

                $totalElements = $query->count();

                $totalSitemaps = ceil($totalElements / $totalElementsPerSitemap);

                $devMode = Craft::$app->config->getGeneral()->devMode;
                $debugString = '';

                if ($devMode) {
                    $debugString =
                        '?siteId=' . $customQuery->siteId
                        . '&sitemapMetadataId=' . $customQuery->id
                        . '&type=' . $customQuery->type;
                }

                // Build Sitemap Index URLs
                for ($i = 1; $i <= $totalSitemaps; $i++) {
                    $sitemapIndexUrl = UrlHelper::siteUrl() . 'sitemap-' . $customQuery->uid . '-' . $i . '.xml' . $debugString;

                    $sitemapIndexPages[] = $sitemapIndexUrl;
                }
            }
        }

        if (SitemapsMetadataHelper::hasCustomPages($site)) {

            $sitemapIndexPages[] = UrlHelper::siteUrl('sitemap-custom-pages.xml');
        }

        return $sitemapIndexPages;
    }

    /**
     * Prepares urls for a dynamic sitemap
     */
    public function getDynamicSitemapElements($sitemapMetadataUid, $sitemapKey, $pageNumber, Site $site): array
    {
        $urls = [];
        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $totalElementsPerSitemap = $this->getTotalElementsPerSitemap();

        $sitemapSites = $this->getSitemapSites();

        // Our offset should be zero for the first page
        $offset = ($totalElementsPerSitemap * $pageNumber) - $totalElementsPerSitemap;

        if ($sitemapKey === SitemapKey::SINGLES) {
            $sitemapMetadataRecords = SitemapsMetadataHelper::getSinglesSitemapMetadata($site);
        } else {
            $sitemapMetadataRecords = SitemapsMetadataHelper::getSitemapMetadataByUid($sitemapMetadataUid, $site);
        }

        foreach ($sitemapMetadataRecords as $sitemapMetadata) {
            if (!$sitemapMetadata->enabled) {
                continue;
            }

            $elementWithUri = $sitemapsService->getElementWithUriByType($sitemapMetadata->type);

            foreach ($sitemapSites as $currentSitemapSite) {

                if (!$elementWithUri) {
                    continue;
                }

                if ($sitemapMetadata->sourceKey === SitemapKey::CUSTOM_QUERY) {

                    $conditionRules = Json::decodeIfJson($sitemapMetadata['settings']);
                    $condition = Craft::$app->conditions->createCondition($conditionRules);
                    // @todo - Save as 'type' in DB. $sitemapMetadata['type']
                    $condition->elementType = $sitemapMetadata->type;

                    $elementQuery = $condition->elementType::find();
                    $condition->modifyQuery($elementQuery);
                } else {
                    $elementQuery = $sitemapMetadata->getElementQuery();
                }

                $elements = $elementQuery
                    ->siteId($currentSitemapSite->id)
                    ->offset($offset)
                    ->limit($totalElementsPerSitemap)
                    ->all();

                if (!$elements) {
                    continue;
                }

                // Add each Element with a URL to the Sitemap
                foreach ($elements as $element) {

                    $canonicalOverride = $metadata['canonical'] ?? null;

                    if (!empty($canonicalOverride)) {
                        Craft::info('Element ID ' . $element->id . ' is using a canonical override and has not been included in the sitemap. Element URL: ' . $element->getUrl() . '. Canonical URL: ' . $canonicalOverride . '.', __METHOD__);
                        continue;
                    }

                    if (!$url = $element->getUrl()) {
                        Craft::info('Element ID ' . $element->id . ' not added to sitemap. Element does not have a URL.', __METHOD__);
                        continue;
                    }

                    // Add each location indexed by its id
                    $urls[$element->id][] = [
                        'id' => $element->id,
                        'url' => $url,
                        'locale' => $currentSitemapSite->language,
                        'modified' => $element->dateUpdated->format('Y-m-d\Th:i:s\Z'),
                        'priority' => $sitemapMetadata['priority'],
                        'changeFrequency' => $sitemapMetadata['changeFrequency'],
                    ];
                }
            }
        }

        return $this->getLocalizedSitemapStructure($urls);
    }

    /**
     * Returns all Custom Section URLs
     */
    public function getCustomPagesUrls(Site $site): array
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

        return $this->getSitemapStructure($urls);
    }

    /**
     * Process Custom Pages Sitemaps for Multi-Lingual Sitemaps that can have custom pages from multiple sections
     */
    public function getCustomPagesUrlsForMultipleIds($siteIds, $sitesInGroup): array
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

        return $this->getSitemapStructure($urls);
    }

    /**
     * Returns all sites to process for the current sitemap request
     */
    public function getSitemapSites(): array
    {
        $settings = SitemapsModule::getInstance()->getSettings();

        $currentSite = Craft::$app->sites->getCurrentSite();
        $isMultisite = Craft::$app->getIsMultiSite();
        $aggregationMethodMultiLingual = $settings->sitemapAggregationMethod === SitemapsSettings::AGGREGATION_METHOD_MULTI_LINGUAL;

        // For multi-lingual sitemaps, get all sites in the Current Site group
        if ($isMultisite && $aggregationMethodMultiLingual && in_array($currentSite->groupId, $settings->getEnabledGroupIds(), false)) {
            return Craft::$app->getSites()->getSitesByGroupId($currentSite->groupId);
        }

        // For non-multi-lingual sitemaps, get the current site
        if (!$aggregationMethodMultiLingual && in_array($currentSite->id, array_filter($settings->getEnabledSiteIds()), false)) {
            return [$currentSite];
        }

        return [];
    }

    /**
     * Returns the value for the totalElementsPerSitemap setting. Default is 500.
     */
    protected function getTotalElementsPerSitemap(int $total = 500): int
    {
        $settings = SitemapsModule::getInstance()->getSettings();

        return $settings->totalElementsPerSitemap ?? $total;
    }

    /**
     * Used for Custom Pages where we localized URLs are managed by where they are stored
     */
    protected function getSitemapStructure(array $urls): array
    {
        $sitemapUrls = [];

        foreach ($urls as $url) {
            $sitemapUrls[] = $url;
        }

        return $sitemapUrls;
    }

    /**
     * Returns an array of localized entries for a sitemap from a set of URLs indexed by id
     *
     * The returned structure is compliant with multiple locale google sitemap spec
     */
    protected function getLocalizedSitemapStructure(array $urls): array
    {
        $localizedSitemapUrls = [];

        /**
         * Looping through all entries indexed by id
         */
        foreach ($urls as $localizedUrls) {
            // Looping through each element and adding it as primary and creating its alternates
            foreach ($localizedUrls as $url) {
                // Add secondary locations as alternatives to primary
                $localizedSitemapUrls[] = (is_countable($localizedUrls) ? count($localizedUrls) : 0) > 1
                    ? array_merge($url, ['alternates' => $localizedUrls])
                    : $url;
            }
        }

        return $localizedSitemapUrls;
    }
}
