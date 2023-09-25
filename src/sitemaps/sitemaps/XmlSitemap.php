<?php

namespace BarrelStrength\Sprout\sitemaps\sitemaps;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ContentQuerySitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ContentSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\CustomPagesSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\models\Site;
use yii\base\Component;

class XmlSitemap extends Component
{
    /**
     * Prepares sitemaps for a sitemapindex
     */
    public function getSitemapIndex(array $sites): array
    {
        $sitemapUrls = [];

        ContentSitemapMetadataHelper::getSitemapUrls($sitemapUrls, $sites);
        ContentQuerySitemapMetadataHelper::getSitemapUrls($sitemapUrls, $sites);
        CustomPagesSitemapMetadataHelper::getSitemapUrls($sitemapUrls, $sites);

        return $sitemapUrls;
    }

    /**
     * Prepares urls for a dynamic sitemap
     *
     * - Content Sitemap: Singles
     * - Content Sitemap: Channel/Structure
     * - Content Query Sitemap
     */
    public function getDynamicSitemapElements($sitemapMetadataUid, $sitemapKey, $pageNumber, array $sitemapSites, Site $site): array
    {
        $urls = [];
        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $totalElementsPerSitemap = SitemapsMetadataHelper::getTotalElementsPerSitemap();
        // Our offset should be zero for the first page
        $offset = ($totalElementsPerSitemap * $pageNumber) - $totalElementsPerSitemap;

        if ($sitemapKey === SitemapKey::SINGLES) {
            $sitemapMetadataRecords = ContentSitemapMetadataHelper::getSinglesSitemapMetadata($site);
        } else {
            // Get Content or Content Query sitemap metadata
            $sitemapMetadataRecords = [SitemapsMetadataHelper::getEnabledSitemapMetadataByUid($sitemapMetadataUid, $site)];
        }

        foreach ($sitemapMetadataRecords as $sitemapMetadata) {
            if (!$sitemapMetadata->enabled) {
                continue;
            }

            $elementWithUris = $sitemapsService->getElementWithUris();

            // Get the Element URI that matches the current Sitemap Metadata type
            $elementWithUri = array_filter($elementWithUris, static function($elementWithUri) use ($sitemapMetadata) {
                return $elementWithUri::class === $sitemapMetadata->type;
            });

            // If we don't have a URI, this isn't a Content or Content Query sitemap that we know how to process
            if (!$elementWithUri) {
                continue;
            }

            foreach ($sitemapSites as $sitemapSite) {

                if ($sitemapMetadata->sourceKey === SitemapKey::CONTENT_QUERY) {
                    $elementQuery = ContentQuerySitemapMetadataHelper::getElementQuery($sitemapMetadata);
                } else {
                    // Content Sitemaps
                    $elementQuery = $sitemapMetadata->getElementQuery();
                }

                $elements = $elementQuery
                    ->siteId($sitemapSite->id)
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
                        'locale' => $sitemapSite->language,
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
