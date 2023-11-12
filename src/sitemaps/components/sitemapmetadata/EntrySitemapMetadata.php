<?php

namespace BarrelStrength\Sprout\sitemaps\components\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ElementSitemapMetadataInterface;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
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
                    $sourceDetails[$section->uid] = [
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
        $sectionId = (new Query())
            ->select('id')
            ->from(Table::SECTIONS)
            ->where(['uid' => $sitemapMetadata->sourceKey])
            ->scalar();

        /** @var EntryQuery $query */
        return $query->sectionId($sectionId);
    }
}
