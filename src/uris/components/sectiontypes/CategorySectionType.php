<?php

namespace BarrelStrength\Sprout\uris\components\sectiontypes;

use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSection;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use Craft;
use craft\base\Model;
use craft\elements\Category as CategoryElement;
use craft\queue\jobs\ResaveElements;

class CategorySectionType extends UrlEnabledSectionType
{
    public function getName(): string
    {
        return 'Category';
    }

    public function getElementIdColumnName(): string
    {
        return 'groupId';
    }

    public function getUrlFormatIdColumnName(): string
    {
        return 'groupId';
    }

    public function getById($id): ?Model
    {
        return Craft::$app->categories->getGroupById($id);
    }

    public function getFieldLayoutSettingsObject($id): ?Model
    {
        return $this->getById($id);
    }

    public function getElementTableName(): string
    {
        return 'categories';
    }

    public function getElementType(): ?string
    {
        return CategoryElement::class;
    }

    public function getMatchedElementVariable(): string
    {
        return 'category';
    }

    /**
     * @return UrlEnabledSection[]
     */
    public function getAllUrlEnabledSections($siteId): array
    {
        $urlEnabledSections = [];

        $sections = Craft::$app->categories->getAllGroups();

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
        return 'categorygroups_sites';
    }

    public function resaveElements($elementGroupId = null): bool
    {
        if (!$elementGroupId) {
            return false;
        }

        $category = Craft::$app->categories->getGroupById($elementGroupId);

        if (!$category) {
            return false;
        }

        $siteSettings = $category->getSiteSettings();

        if (!$siteSettings) {
            return false;
        }

        // let's take the first site
        $primarySite = reset($siteSettings)->siteId ?? null;

        if (!$primarySite) {
            return false;
        }

        Craft::$app->getQueue()->push(new ResaveElements([
            'description' => Craft::t('sprout-module-uris', 'Re-saving Categories and metadata.'),
            'elementType' => CategoryElement::class,
            'criteria' => [
                'siteId' => $primarySite,
                'groupId' => $elementGroupId,
                'status' => null,
                'enabledForSite' => false,
                'limit' => null,
            ],
        ]));

        return true;
    }
}
