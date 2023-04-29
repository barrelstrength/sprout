<?php

namespace BarrelStrength\Sprout\uris\components\sectiontypes;

use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSection;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use craft\base\Model;
use craft\commerce\elements\Product as ProductElement;
use craft\commerce\services\ProductTypes;

class ProductSectionType extends UrlEnabledSectionType
{
    public function getName(): string
    {
        return 'Product';
    }

    public function getElementIdColumnName(): string
    {
        return 'typeId';
    }

    public function getUrlFormatIdColumnName(): string
    {
        return 'productTypeId';
    }

    public function getById($id): ?Model
    {
        return (new ProductTypes())->getProductTypeById($id);
    }

    public function getFieldLayoutSettingsObject($id): ?Model
    {
        return $this->getById($id);
    }

    public function getElementTableName(): string
    {
        return 'commerce_products';
    }

    public function getElementType(): ?string
    {
        return ProductElement::class;
    }

    public function getMatchedElementVariable(): string
    {
        return 'product';
    }

    /**
     * @return UrlEnabledSection[]
     */
    public function getAllUrlEnabledSections($siteId): array
    {
        $urlEnabledSections = [];

        $productTypes = new ProductTypes();

        $sections = $productTypes->getAllProductTypes();

        foreach ($sections as $section) {
            $siteSettings = $section->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($siteId == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $urlEnabledSections[] = $section;
                }
            }
        }

        return $urlEnabledSections;
    }

    public function getTableName(): string
    {
        return 'commerce_producttypes_sites';
    }
}
