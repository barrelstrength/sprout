<?php

namespace BarrelStrength\Sprout\sitemaps\components\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ElementSitemapMetadataInterface;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\models\Site;

class EntrySitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(Site $site): array
    {
        $sections = Craft::$app->sections->getAllSections();

        foreach ($sections as $section) {
            $siteSettings = $section->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($site->id == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $sourceKey = Entry::pluralLowerDisplayName() . '-' . $section->id;
                    $sourceDetails[$sourceKey] = [
                        'type' => Entry::class,
                        'name' => $section->name,
                        'urlPattern' => $siteSetting->uriFormat,
                    ];
                }
            }
        }

        return $sourceDetails ?? [];
    }

    public function getElementQuery(ElementQuery $query, SitemapMetadataRecord $sitemapMetadata): ElementQuery
    {
        $sectionId = SitemapsMetadataHelper::findElementGroupId($sitemapMetadata->sourceKey);

        return $query->sectionId($sectionId);
    }
}
