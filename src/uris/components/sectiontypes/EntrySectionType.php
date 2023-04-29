<?php

namespace BarrelStrength\Sprout\uris\components\sectiontypes;

use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSection;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use Craft;
use craft\base\Model;
use craft\elements\Entry as EntryElement;

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
}
