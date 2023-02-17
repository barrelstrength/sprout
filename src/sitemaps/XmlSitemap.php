<?php

namespace BarrelStrength\Sprout\sitemaps;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemapsections\SitemapSectionRecord;
use BarrelStrength\Sprout\uris\components\sectiontypes\EntrySectionType;
use BarrelStrength\Sprout\uris\components\sectiontypes\NoSectionSectionType;
use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\UrlHelper;
use DateTime;
use yii\base\Component;

class XmlSitemap extends Component
{
    /**
     * Prepares sitemaps for a sitemapindex
     */
    public function getSitemapIndex($siteId = null): array
    {
        $sitemapIndexPages = [];
        $hasSingles = false;

        $totalElementsPerSitemap = $this->getTotalElementsPerSitemap();

        $urlEnabledSectionTypes = SitemapsModule::getInstance()->sitemaps->getUrlEnabledSectionTypesForSitemaps($siteId);

        foreach ($urlEnabledSectionTypes as $urlEnabledSectionType) {
            $urlEnabledSectionTypeId = $urlEnabledSectionType->getElementIdColumnName();

            foreach ($urlEnabledSectionType->urlEnabledSections as $urlEnabledSection) {
                $sitemapSection = $urlEnabledSection->sitemapSection;

                if ($sitemapSection->enabled) {
                    $elementClassName = $urlEnabledSectionType->getElementType();
                    /** @var Element $element */
                    $element = new $elementClassName();
                    /** Get Total Elements for this URL-Enabled Section @var ElementQuery $query */
                    $query = $element::find();
                    $query->{$urlEnabledSectionTypeId}($urlEnabledSection->id);
                    $query->siteId($siteId);

                    $totalElements = $query->count();

                    // Is this a Singles Section?
                    $section = $urlEnabledSectionType->getById($urlEnabledSection->id);

                    if ($section && isset($section->type) && $section->type === 'single') {
                        // only add this once
                        if (!$hasSingles) {
                            $hasSingles = true;

                            // Add the singles at the beginning of our sitemap
                            array_unshift($sitemapIndexPages, UrlHelper::siteUrl() . 'sitemap-singles.xml');
                        }
                    } else {
                        $totalSitemaps = ceil($totalElements / $totalElementsPerSitemap);

                        $devMode = Craft::$app->config->getGeneral()->devMode;
                        $debugString = '';

                        if ($devMode) {
                            $debugString =
                                '?devMode=true'
                                . '&siteId=' . $sitemapSection->siteId
                                . '&urlEnabledSectionId=' . $sitemapSection->urlEnabledSectionId
                                . '&sitemapSectionId=' . $sitemapSection->id
                                . '&type=' . $sitemapSection->type
                                . '&handle=' . $sitemapSection->handle;
                        }

                        // Build Sitemap Index URLs
                        for ($i = 1; $i <= $totalSitemaps; $i++) {

                            $sitemapIndexUrl = UrlHelper::siteUrl() . 'sitemap-' . $sitemapSection->uniqueKey . '-' . $i . '.xml' . $debugString;
                            $sitemapIndexPages[] = $sitemapIndexUrl;
                        }
                    }
                }
            }
        }

        // Fetching all Custom Sitemap defined in Sprout SEO
        $customSitemapSections = (new Query())
            ->select('id')
            ->from([SproutTable::SITEMAPS])
            ->where(['enabled' => true])
            ->andWhere('type=:type', [':type' => NoSectionSectionType::class])
            ->andWhere(['not', ['uri' => null]])
            ->count();

        if ($customSitemapSections > 0) {
            $sitemapIndexPages[] = UrlHelper::siteUrl('sitemap-custom-pages.xml');
        }

        return $sitemapIndexPages;
    }

    /**
     * Prepares urls for a dynamic sitemap
     */
    public function getDynamicSitemapElements($sitemapKey, $pageNumber, $siteId): array
    {
        $urls = [];

        $totalElementsPerSitemap = $this->getTotalElementsPerSitemap();

        $currentSitemapSites = $this->getCurrentSitemapSites();

        // Our offset should be zero for the first page
        $offset = ($totalElementsPerSitemap * $pageNumber) - $totalElementsPerSitemap;

        $enabledSitemapSections = $this->getEnabledSitemapSections($sitemapKey, $siteId);

        foreach ($enabledSitemapSections as $sitemapSection) {

            $urlEnabledSectionType = SitemapsModule::getInstance()->sitemaps->getUrlEnabledSectionTypeByType($sitemapSection->type);
            $sectionModel = $urlEnabledSectionType->getById($sitemapSection->urlEnabledSectionId);

            foreach ($currentSitemapSites as $site) {

                #$globalMetadata = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($site);

                $elements = [];

                if ($urlEnabledSectionType !== null) {

                    $elementClassName = $urlEnabledSectionType->getElementType();
                    /** @var Element $element */
                    $element = new $elementClassName();
                    /** Get Total Elements for this URL-Enabled Section @var ElementQuery $query */
                    $query = $element::find();

                    // Example: $query->sectionId(123)
                    $urlEnabledSectionColumnName = $urlEnabledSectionType->getElementIdColumnName();
                    $query->{$urlEnabledSectionColumnName}($sitemapSection->urlEnabledSectionId);

                    $query->offset($offset);
                    $query->limit($totalElementsPerSitemap);
                    $query->site($site);
                    $query->status(Element::STATUS_ENABLED);

                    if ($urlEnabledSectionType->getElementLiveStatus()) {
                        $query->status($urlEnabledSectionType->getElementLiveStatus());
                    }

                    // @todo - review. Almost the same code runs for both conditions.
                    if ($sitemapKey === 'singles') {
                        if ($sectionModel && isset($sectionModel->type) && $sectionModel->type === 'single') {
                            $elements = $query->all();
                        }
                    } else {
                        $elements = $query->all();
                    }
                }

                // Add each Element with a URL to the Sitemap
                foreach ($elements as $element) {
                    // @todo figure out how handle this code
                    /*
                    if ($elementMetadataFieldHandle === null) {
                        $elementMetadataFieldHandle = MetaModule::getInstance()->elementMetadata->getElementMetadataFieldHandle($element);
                    }

                    $robots = null;

                    // If we have an Element Metadata field, allow it to override robots
                    if ($elementMetadataFieldHandle) {
                        $metadata = $element->{$elementMetadataFieldHandle};

                        if (isset($metadata['enableMetaDetailsRobots']) && !empty($metadata['enableMetaDetailsRobots'])) {
                            $robots = $metadata['robots'] ?? null;
                            $robots = OptimizeMetadataHelper::prepareRobotsMetadataForSettings($robots);
                        }
                    }

                    $noIndex = $robots['noindex'] ?? $globalMetadata['robots']['noindex'] ?? null;
                    $noFollow = $robots['nofollow'] ?? $globalMetadata['robots']['nofollow'] ?? null;

                    if ($noIndex == 1 OR $noFollow == 1) {
                        Craft::info('Element ID '.$element->id.' not added to sitemap. Element Metadata field `noindex` or `nofollow` settings are enabled.', __METHOD__);
                        continue;
                    }
                    * */

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
                        'locale' => $site->language,
                        'modified' => $element->dateUpdated->format('Y-m-d\Th:i:s\Z'),
                        'priority' => $sitemapSection['priority'],
                        'changeFrequency' => $sitemapSection['changeFrequency'],
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
    public function getCustomSectionUrls($siteId): array
    {
        $urls = [];

        // Fetch all Custom Sitemap defined in Sprout SEO
        $customSitemapSections = (new Query())
            ->select('uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS])
            ->where(['enabled' => true])
            ->andWhere('[[siteId]] = :siteId', [':siteId' => $siteId])
            ->andWhere('type=:type', [':type' => NoSectionSectionType::class])
            ->all();

        foreach ($customSitemapSections as $customSitemapSection) {
            $customSitemapSection['url'] = null;
            // Adding each custom location indexed by its URL
            if (!UrlHelper::isAbsoluteUrl($customSitemapSection['uri'])) {
                $customSitemapSection['url'] = UrlHelper::siteUrl($customSitemapSection['uri']);
            }

            $modified = new DateTime($customSitemapSection['dateUpdated']);
            $customSitemapSection['modified'] = $modified->format('Y-m-d\Th:i:s\Z');

            $urls[$customSitemapSection['uri']] = $customSitemapSection;
        }

        return $this->getLocalizedSitemapStructure($urls);
    }

    /**
     * Process Custom Pages Sitemaps for Multi-Lingual Sitemaps that can have custom pages from multiple sections
     */
    public function getCustomSectionUrlsForMultipleIds($siteIds, $sitesInGroup): array
    {
        $urls = [];

        $customSitemapSections = (new Query())
            ->select('[[siteId]], uri, priority, [[changeFrequency]], [[dateUpdated]]')
            ->from([SproutTable::SITEMAPS])
            ->where(['enabled' => true])
            ->andWhere(['[[siteId]]' => $siteIds])
            ->andWhere('type=:type', [':type' => NoSectionSectionType::class])
            ->indexBy('[[siteId]]')
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {
            foreach ($customSitemapSections as $customSitemapSection) {
                if ($siteInGroup->id !== $customSitemapSection['siteId']) {
                    continue;
                }

                $customSitemapSection['url'] = null;
                // Adding each custom location indexed by its URL

                $url = Craft::getAlias($siteInGroup->baseUrl) . $customSitemapSection['uri'];
                $customSitemapSection['url'] = $url;

                $modified = new DateTime($customSitemapSection['dateUpdated']);
                $customSitemapSection['modified'] = $modified->format('Y-m-d\Th:i:s\Z');

                $urls[$customSitemapSection['uri']] = $customSitemapSection;
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

    /**
     * Remove Slash from URI
     */
    public function removeSlash(string $uri): string
    {
        $slash = '/';

        if (isset($uri[0]) && $uri[0] == $slash) {
            $uri = ltrim($uri, $slash);
        }

        return $uri;
    }

    protected function getEnabledSitemapSections($sitemapKey, $siteId): array
    {
        $query = SitemapSectionRecord::find()
            ->where('enabled = true and [[urlEnabledSectionId]] is not null')
            ->andWhere('[[siteId]] = :siteId', [':siteId' => $siteId]);

        if ($sitemapKey == 'singles') {
            $query->andWhere('type = :type', [':type' => EntrySectionType::class]);
        } else {
            $query->andWhere('[[uniqueKey]] = :uniqueKey', [':uniqueKey' => $sitemapKey]);
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
