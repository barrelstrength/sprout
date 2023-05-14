<?php

namespace BarrelStrength\Sprout\sitemaps\components\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ElementSitemapMetadataInterface;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use Craft;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\models\Site;

class CategorySitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(Site $site): array
    {
        $categoryGroups = Craft::$app->categories->getAllGroups();

        foreach ($categoryGroups as $categoryGroup) {
            $siteSettings = $categoryGroup->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($site->id == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $sourceKey = Category::pluralLowerDisplayName() . '-' . $categoryGroup->id;
                    $sourceDetails[$sourceKey] = [
                        'type' => Category::class,
                        'name' => $categoryGroup->name,
                        'urlPattern' => $siteSetting->uriFormat,
                    ];
                }
            }
        }

        return $sourceDetails ?? [];
    }

    public function getElementQuery(ElementQuery $query, SitemapMetadataRecord $sitemapMetadata): ElementQuery
    {
        $categoryGroupId = SitemapsMetadataHelper::findElementGroupId($sitemapMetadata->sourceKey);

        return $query->groupId($categoryGroupId);
    }
}
