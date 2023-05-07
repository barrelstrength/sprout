<?php

namespace BarrelStrength\Sprout\sitemaps\components;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\metadata\SourceKeyHelper;
use craft\commerce\elements\Product;
use craft\commerce\Plugin as CraftCommerce;
use craft\elements\db\ElementQuery;
use craft\helpers\Cp;

class ProductSitemapMetadata implements ElementSitemapMetadataInterface
{
    public static function getSourceDetails(): array
    {
        $site = Cp::requestedSite();

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
