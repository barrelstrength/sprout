<?php

namespace BarrelStrength\Sprout\sitemaps\components;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\metadata\SourceKeyHelper;
use Craft;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\helpers\Cp;

class CategorySitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(): array
    {
        $site = Cp::requestedSite();

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
        $categoryGroupId = SourceKeyHelper::findElementGroupId($sitemapMetadata->sourceKey);

        return $query->groupId($categoryGroupId);
    }
}
