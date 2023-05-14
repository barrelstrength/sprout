<?php

namespace BarrelStrength\Sprout\sitemaps\components\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ElementSitemapMetadataInterface;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SourceKeyHelper;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as CraftCommerce;
use craft\elements\db\ElementQuery;
use craft\models\Site;

class ProductSitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(Site $site): array
    {
        $productTypes = CraftCommerce::getInstance()->productTypes->getAllProductTypes();

        foreach ($productTypes as $productType) {
            $siteSettings = $productType->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($site->id == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $sourceKey = Product::pluralLowerDisplayName() . '-' . $productType->id;
                    $sourceDetails[$sourceKey] = [
                        'type' => Product::class,
                        'name' => $productType->name,
                        'urlPattern' => $siteSetting->uriFormat,
                    ];
                }
            }
        }

        return $sourceDetails ?? [];
    }

    public function getElementQuery(ElementQuery $query, SitemapMetadataRecord $sitemapMetadata): ElementQuery
    {
        $productTypeId = SourceKeyHelper::findElementGroupId($sitemapMetadata->sourceKey);

        return $query->typeId($productTypeId);
    }
}
