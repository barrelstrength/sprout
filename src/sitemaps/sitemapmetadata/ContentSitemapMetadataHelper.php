<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Site;

class ContentSitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, array $sites): void
    {
        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $hasSingles = false;

        $elementsWithUris = $sitemapsService->getElementWithUris();

        // Single Site - $sites should be an array of one site
        // Multi-Site - first site in list is the first in the site group
        $firstSiteInGroup = reset($sites);

        $contentSitemapMetadata = self::getContentSitemapMetadata($firstSiteInGroup);

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
                $isSingle = self::isSinglesSection($sitemapMetadata);

                // Make sure we don't add Singles more than once
                if ($isSingle && $hasSingles) {
                    continue;
                }

                if ($isSingle) {
                    $hasSingles = true;

                    // Add the singles at the beginning of our sitemap
                    array_unshift(
                        $sitemapUrls,
                        UrlHelper::siteUrl() . 'sitemap-singles.xml'
                    );
                } else {
                    SitemapsMetadataHelper::getPaginatedSitemapUrls($sitemapUrls, $sitemapMetadata, $totalElements);
                }
            }
        }
    }

    /**
     * Get Sitemap Metadata related to all Element Groups
     *
     * Results by the UID of the source Element
     * - Section UID - Entries
     * - Category Group UID - Categories
     * - Product Type UID - Products
     */
    public static function getContentSitemapMetadata(Site $site): array
    {
        $sourceDetails = self::getSourceDetails($site);

        /** @var SitemapMetadataRecord[] $sitemapMetadataRecords */
        $sitemapMetadataRecords = SitemapMetadataRecord::find()
            ->where(['[[siteId]]' => $site->id])
            ->andWhere([
                'not in', 'sourceKey', [
                    SitemapKey::SINGLES,
                    SitemapKey::CONTENT_QUERY,
                    SitemapKey::CUSTOM_PAGES,
                ],
            ])
            ->indexBy('sourceKey')
            ->all();

        $sitemapMetadata = [];

        foreach ($sourceDetails as $sourceUid => $sourceDetail) {
            $record = $sitemapMetadataRecords[$sourceUid] ?? new SitemapMetadataRecord();

            $record->type = $sourceDetail['type'] ?? null;
            $record->name = $sourceDetail['name'] ?? null;
            $record->uri = $sourceDetail['urlPattern'] ?? null;

            $sitemapMetadata[$sourceUid] = $record;
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

    public static function getSourceDetails(Site $site): array
    {
        /** @var ElementSitemapMetadataInterface[] $sitemapMetadataTypes */
        $sitemapMetadataTypes = SitemapsModule::getInstance()->sitemaps->getSitemapMetadataTypes();

        $sourceDetails = [];

        foreach ($sitemapMetadataTypes as $sitemapMetadataIntegration) {
            foreach ($sitemapMetadataIntegration::getSourceDetails($site) as $sourceKey => $sourceDetail) {
                $sourceDetails[$sourceKey] = $sourceDetail;
            }
        }

        return $sourceDetails;
    }

    /**
     * Lite users can use the full features of Sitemaps but are limited
     * to a total of 5 Content or Content Query Sitemaps per site
     */
    public static function hasReachedSitemapLimit($siteId): bool
    {
        if (SitemapsModule::isPro()) {
            // no limit for pro users
            return false;
        }

        $count = SitemapMetadataRecord::find()
            ->where(['[[siteId]]' => $siteId])
            ->andWhere(['enabled' => true])
            ->andWhere([
                'not in', 'sourceKey', [
                    SitemapKey::CUSTOM_PAGES,
                ],
            ])
            ->indexBy('sourceKey')
            ->count();

        return $count >= 5;
    }
}
