<?php

namespace BarrelStrength\Sprout\meta\components\meta;

use BarrelStrength\Sprout\meta\metadata\MetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;

class GeoMetaType extends MetaType
{
    protected ?string $region = null;

    protected ?string $placename = null;

    protected ?string $position = null;

    protected ?string $latitude = null;

    protected ?string $longitude = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Geo');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'region';
        $attributes[] = 'placename';
        $attributes[] = 'position';
        $attributes[] = 'longitude';
        $attributes[] = 'latitude';

        return $attributes;
    }

    public function getAttributesMapping(): array
    {
        return [
            'region' => 'geo.region',
            'placename' => 'geo.placename',
            'position' => 'geo.position',
        ];
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $value): void
    {
        $this->region = $value;
    }

    public function getPlacename(): ?string
    {
        return $this->placename;
    }

    public function setPlacename(?string $value): void
    {
        $this->placename = $value;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $value): void
    {
        $this->position = $value;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $value): void
    {
        $this->latitude = $value;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $value): void
    {
        $this->longitude = $value;
    }

    public function getHandle(): string
    {
        return 'geo';
    }

    public function getIconPath(): string
    {
        return '@Sprout/Assets/dist/static/meta/icons/map-marker-alt.svg';
    }

    public function getSettingsHtml(Field $field): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-meta/_components/fields/elementmetadata/blocks/geo', [
            'meta' => $this,
            'field' => $field,
        ]);
    }

    public function showMetaDetailsTab(): bool
    {
        return MetaModule::getInstance()->optimizeMetadata->elementMetadataField->showGeo;
    }

    public function getMetaTagData(): array
    {
        $tagData = [];

        foreach ($this->getAttributes() as $key => $value) {
            if ($key === 'latitude' || $key === 'longitude') {
                break;
            }

            $value = $this->{$key};

            if ($key === 'position') {
                $value = $this->prepareGeoPosition();
            }

            if ($value) {
                $tagData[$this->getMetaTagName($key)] = $value;
            }
        }

        return $tagData;
    }

    /**
     * Set the geo 'position' attribute based on the 'latitude' and 'longitude'
     */
    protected function prepareGeoPosition(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ';' . $this->longitude;
        }

        return $this->position;
    }
}
