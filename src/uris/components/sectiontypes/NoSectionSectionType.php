<?php

namespace BarrelStrength\Sprout\uris\components\sectiontypes;

use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use craft\base\Model;

class NoSectionSectionType extends UrlEnabledSectionType
{

    public function getName(): string
    {
        return 'No Section';
    }

    public function getId(): string
    {
        return 'none';
    }

    public function getElementIdColumnName(): string
    {
        return '';
    }

    public function getUrlFormatIdColumnName(): string
    {
        return '';
    }

    public function getById($id): ?Model
    {
        return null;
    }

    public function getFieldLayoutSettingsObject($id): ?Model
    {
        return null;
    }

    public function getElementTableName(): string
    {
        return '';
    }

    public function getElementType(): ?string
    {
        return null;
    }

    public function getMatchedElementVariable(): string
    {
        return '';
    }

    public function getAllUrlEnabledSections($siteId): array
    {
        return [];
    }

    public function getTableName(): string
    {
        return '';
    }

    public function resaveElements($elementGroupId = null): bool
    {
        return true;
    }
}
