<?php

namespace BarrelStrength\Sprout\sitemaps\components;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\metadata\SourceKeyHelper;
use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\Cp;

class EntrySitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(): array
    {
        $site = Cp::requestedSite();

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
        $sectionId = SourceKeyHelper::findElementGroupId($sitemapMetadata->sourceKey);

        return $query->sectionId($sectionId);
    }
}
