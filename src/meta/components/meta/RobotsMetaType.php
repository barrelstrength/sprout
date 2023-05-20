<?php

namespace BarrelStrength\Sprout\meta\components\meta;

use BarrelStrength\Sprout\meta\metadata\MetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;

class RobotsMetaType extends MetaType
{
    protected ?string $canonical = null;

    protected ?string $robots = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Robots');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'canonical';
        $attributes[] = 'robots';

        return $attributes;
    }

    public function getCanonical(): ?string
    {
        if ($this->canonical || $this->metadata->getRawDataOnly()) {
            return $this->canonical;
        }

        return $this->metadata->getCanonical();
    }

    public function setCanonical(?string $value): void
    {
        $this->canonical = $value;
    }

    public function getRobots()
    {
        if ($this->robots || $this->metadata->getRawDataOnly()) {
            return $this->robots;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals['robots'] ?? null;
    }

    public function setRobots($value): void
    {
        $this->robots = MetaModule::getInstance()->optimizeMetadata->prepareRobotsMetadataValue($value);
    }

    public function getHandle(): string
    {
        return 'robots';
    }

    public function getIconPath(): string
    {
        return '@Sprout/Assets/dist/static/meta/icons/search-minus.svg';
    }

    public function getSettingsHtml(Field $field): string
    {
        $robotsNamespace = $field->handle . '[metadata][robots]';
        $robots = MetaModule::getInstance()->optimizeMetadata->prepareRobotsMetadataForSettings($this->robots);

        return Craft::$app->getView()->renderTemplate('sprout-module-meta/_components/fields/elementmetadata/blocks/robots.twig', [
            'meta' => $this,
            'field' => $field,
            'robotsNamespace' => $robotsNamespace,
            'robots' => $robots,
        ]);
    }

    public function showMetaDetailsTab(): bool
    {
        return MetaModule::getInstance()->optimizeMetadata->elementMetadataField->showRobots;
    }
}
