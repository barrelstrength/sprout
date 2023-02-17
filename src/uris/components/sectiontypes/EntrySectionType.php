<?php

namespace BarrelStrength\Sprout\uris\components\sectiontypes;

use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSection;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use Craft;
use craft\base\Model;
use craft\elements\Entry as EntryElement;
use craft\queue\jobs\ResaveElements;

class EntrySectionType extends UrlEnabledSectionType
{
    public function getName(): string
    {
        return 'Entry';
    }

    public function getElementIdColumnName(): string
    {
        return 'sectionId';
    }

    public function getUrlFormatIdColumnName(): string
    {
        return 'sectionId';
    }

    public function getById($id): ?Model
    {
        return Craft::$app->sections->getSectionById($id);
    }

    public function getFieldLayoutSettingsObject($id): ?Model
    {
        $section = $this->getById($id);

        if (!$section instanceof Model) {
            return null;
        }

        return $section->getEntryTypes();
    }

    public function getElementTableName(): string
    {
        return 'entries';
    }

    public function getElementType(): ?string
    {
        return EntryElement::class;
    }

    public function getElementLiveStatus(): string
    {
        return EntryElement::STATUS_LIVE;
    }

    public function getMatchedElementVariable(): string
    {
        return 'entry';
    }

    /**
     * @return UrlEnabledSection[]
     */
    public function getAllUrlEnabledSections($siteId): array
    {
        $urlEnabledSections = [];

        $sections = Craft::$app->sections->getAllSections();

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
        return 'sections_sites';
    }

    public function resaveElements($elementGroupId = null): bool
    {
        if (!$elementGroupId) {
            return false;
        }

        $section = Craft::$app->sections->getSectionById($elementGroupId);

        if (!$section) {
            return false;
        }

        $siteSettings = $section->getSiteSettings();

        if (!$siteSettings) {
            return false;
        }

        // let's take the first site
        $primarySite = reset($siteSettings)->siteId ?? null;

        if (!$primarySite) {
            return false;
        }

        Craft::$app->getQueue()->push(new ResaveElements([
            'description' => Craft::t('sprout-module-uris', 'Re-saving Entries and metadata'),
            'elementType' => EntryElement::class,
            'criteria' => [
                'siteId' => $primarySite,
                'sectionId' => $elementGroupId,
                'status' => null,
                'enabledForSite' => false,
                'limit' => null,
            ],
        ]));

        return true;
    }
}
