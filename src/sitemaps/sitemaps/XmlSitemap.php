<?php

namespace BarrelStrength\Sprout\sitemaps\sitemaps;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadata;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\db\Query;
use craft\elements\Entry;
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

        $sitemapMetadataByKey = $sitemapsService->getSitemapMetadataByKey($site);

        foreach ($elementsWithUris as $elementWithUri) {

            foreach ($sitemapMetadataByKey as $sitemapMetadata) {
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
                            '?devMode=true'
                            . '&siteId=' . $sitemapMetadata->siteId
                            . '&element=' . $elementWithUri::displayName()
                            . '&sourceKey=' . $sitemapMetadata->sourceKey
                            . '&sitemapMetadataId=' . $sitemapMetadata->id
                            . '&handle=' . $sitemapMetadata->handle;
                    }

                    // Build Sitemap Index URLs
                    for ($i = 1; $i <= $totalSitemaps; $i++) {
                        $sitemapIndexUrl = UrlHelper::siteUrl() . 'sitemap-' . $sitemapMetadata->sitemapKey . '-' . $i . '.xml' . $debugString;

                        $sitemapIndexPages[] = $sitemapIndexUrl;
                    }
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
    public function getDynamicSitemapElements($sitemapKey, $pageNumber, Site $site): array
    {
        $urls = [];
        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $totalElementsPerSitemap = $this->getTotalElementsPerSitemap();

        $currentSitemapSites = $this->getCurrentSitemapSites();

        // Our offset should be zero for the first page
        $offset = ($totalElementsPerSitemap * $pageNumber) - $totalElementsPerSitemap;

        $enabledSitemapSections = $this->getEnabledSitemapSections($sitemapKey, $site);

        foreach ($enabledSitemapSections as $sitemapMetadata) {

            if (!$sitemapMetadata->enabled) {
                continue;
            }

            $elementWithUri = $sitemapsService->getElementWithUriByType($sitemapMetadata->type);

            foreach ($currentSitemapSites as $currentSitemapSite) {

                if (!$elementWithUri) {
                    continue;
                }

                $elementQuery = $sitemapMetadata->getElementQuery();
                $elements = $elementQuery
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

                    if ($element->getUrl() === null) {
                        Craft::info('Element ID ' . $element->id . ' not added to sitemap. Element does not have a URL.', __METHOD__);
                        continue;
                    }

                    // Add each location indexed by its id
                    $urls[$element->id][] = [
                        'id' => $element->id,
                        'url' => $element->getUrl(),
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
     * Returns all sites to process for the current sitemap request
     */
    public function getCurrentSitemapSites(): array
    {
        $pluginSettings = SitemapsModule::getInstance()->getSettings();

        $currentSite = Craft::$app->sites->getCurrentSite();
        $isMultisite = Craft::$app->getIsMultiSite();

        // For multi-lingual sitemaps, get all sites in the Current Site group
        if ($isMultisite && in_array($currentSite->groupId, $pluginSettings->groupSettings, false)) {
            return Craft::$app->getSites()->getSitesByGroupId($currentSite->groupId);
        }

        // For non-multi-lingual sitemaps, get the current site
        if (!$isMultisite && in_array($currentSite->id, array_filter($pluginSettings->siteSettings), false)) {
            return [$currentSite];
        }

        return [];
    }

    /**
     * Returns all Custom Section URLs
     */
    public function getCustomSectionUrls(Site $site): array
    {
        $urls = [];

        // Fetch all Custom Sitemap defined in Sprout SEO
        $sitemapMetadata = (new Query())
            ->select('uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['[[siteId]]' => $site->id])
            ->andWhere(['[[type]]' => SitemapMetadata::NO_ELEMENT_TYPE])
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

        return $this->getLocalizedSitemapStructure($urls);
    }

    /**
     * Process Custom Pages Sitemaps for Multi-Lingual Sitemaps that can have custom pages from multiple sections
     */
    public function getCustomSectionUrlsForMultipleIds($siteIds, $sitesInGroup): array
    {
        $urls = [];

        $sitemapMetadata = (new Query())
            ->select('[[siteId]], uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['[[siteId]]' => $siteIds])
            ->andWhere(['type' => SitemapMetadata::NO_ELEMENT_TYPE])
            ->indexBy('[[siteId]]')
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {
            foreach ($sitemapMetadata as $sitemapMetadataGroup) {
                if ($siteInGroup->id !== $sitemapMetadataGroup['siteId']) {
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

        return $this->getLocalizedSitemapStructure($urls);
    }

    /**
     * Returns the value for the totalElementsPerSitemap setting. Default is 500.
     */
    public function getTotalElementsPerSitemap(int $total = 500): int
    {
        $settings = SitemapsModule::getInstance()->getSettings();

        return $settings->totalElementsPerSitemap ?? $total;
    }

    protected function getEnabledSitemapSections($sitemapKey, Site $site): array
    {
        $query = SitemapMetadataRecord::find()
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['not', ['type' => SitemapMetadata::NO_ELEMENT_TYPE]]);

        // @todo - review this logic
        if ($sitemapKey == SitemapKey::SINGLES) {
            $query->andWhere(['type' => Entry::class]);
        } else {
            $query->andWhere(['sitemapKey' => $sitemapKey]);
        }

        return $query->all();
    }

    /**
     * Returns an array of localized entries for a sitemap from a set of URLs indexed by id
     *
     * The returned structure is compliant with multiple locale google sitemap spec
     */
    protected function getLocalizedSitemapStructure(array $stack): array
    {
        // Defining the containing structure
        $structure = [];

        /**
         * Looping through all entries indexed by id
         */
        foreach ($stack as $id => $locations) {
            if (is_string($id)) {
                // Adding a custom location indexed by its URL
                $structure[] = $locations;
            } else {
                // Looping through each element and adding it as primary and creating its alternates
                foreach ($locations as $location) {
                    // Add secondary locations as alternatives to primary
                    $structure[] = (is_countable($locations) ? count($locations) : 0) > 1 ? array_merge($location, ['alternates' => $locations]) : $location;
                }
            }
        }

        return $structure;
    }
}
