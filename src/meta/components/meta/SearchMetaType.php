<?php

namespace BarrelStrength\Sprout\meta\components\meta;

use BarrelStrength\Sprout\meta\metadata\MetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;

class SearchMetaType extends MetaType
{
    protected ?string $title = null;

    protected ?string $appendTitleValue = null;

    protected ?string $description = null;

    protected ?string $keywords = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Search');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'title';
        $attributes[] = 'description';
        $attributes[] = 'keywords';

        return $attributes;
    }

    public function getTitle(bool $appendTitle = true): ?string
    {
        $appendTitleString = '';

        if ($appendTitle) {
            $appendTitleString = ' ' . $this->getAppendTitleValue();
        }

        // In the CP we only save the raw data
        if ($this->title || $this->metadata->getRawDataOnly()) {
            return trim($this->title . $appendTitleString) ?: null;
        }

        // On the front-end, fall back to optimized values
        if ($optimizedTitle = $this->metadata->getOptimizedTitle()) {
            return trim($optimizedTitle . $appendTitleString) ?: null;
        }

        $identity = MetaModule::getInstance()->optimizeMetadata->globals->getIdentity();

        return isset($identity['name']) ? trim($identity['name']) : null;
    }

    public function setTitle(?string $value): void
    {
        $this->title = $value;
    }

    public function getAppendTitleValue(): ?string
    {
        if ($this->appendTitleValue || $this->metadata->getRawDataOnly()) {
            return $this->appendTitleValue;
        }

        $settings = MetaModule::getInstance()->optimizeMetadata->globals->getSettings();

        if ($settings === null) {
            return null;
        }

        $appendTitleValue = null;
        $appendTitleValueOnHomepage = $settings['appendTitleValueOnHomepage'];
        $metaDivider = $settings['metaDivider'];

        if ($appendTitleValueOnHomepage || Craft::$app->request->getPathInfo()) {
            $globalAppendTitleValue = $settings['appendTitleValue'];

            $currentSite = Craft::$app->getSites()->getCurrentSite();

            // Add support for using {divider} and {siteName} in the Sitemap 'Append Meta Title' setting
            $appendTitleValue = Craft::$app->view->renderObjectTemplate($globalAppendTitleValue, [
                'siteName' => $currentSite->name,
                'divider' => $metaDivider,
            ]);

            $appendTitleValue = $metaDivider . ' ' . $appendTitleValue;
        }

        $this->appendTitleValue = $appendTitleValue;

        return $appendTitleValue;
    }

    public function setAppendTitleValue(?string $value): void
    {
        $this->appendTitleValue = $value;
    }

    public function getDescription(): ?string
    {
        $descriptionLength = MetaModule::getInstance()->elementMetadata->getDescriptionLength();

        // In the CP we only save the raw data
        if ($this->description || $this->metadata->getRawDataOnly()) {
            return mb_substr($this->description, 0, $descriptionLength) ?: null;
        }

        // On the front-end, fall back to optimized values
        if ($optimizedDescription = $this->metadata->getOptimizedDescription()) {
            return mb_substr($optimizedDescription, 0, $descriptionLength) ?: null;
        }

        $globalDescription = MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['description'] ?? null;

        return mb_substr($globalDescription, 0, $descriptionLength) ?: null;
    }

    public function setDescription(?string $value): void
    {
        $this->description = $value;
    }

    public function getKeywords()
    {
        // In the CP we only save the raw data
        if ($this->keywords || $this->metadata->getRawDataOnly()) {
            return $this->keywords;
        }

        // On the front-end, fall back to optimized values
        if ($optimizedKeywords = $this->metadata->getOptimizedKeywords()) {
            return $optimizedKeywords;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['keywords'] ?? null;
    }

    public function setKeywords(?string $value): void
    {
        $this->keywords = $value;
    }

    public function getHandle(): string
    {
        return 'search';
    }

    public function getIconPath(): string
    {
        return '@Sprout/Assets/dist/static/meta/icons/search.svg';
    }

    public function showMetaDetailsTab(): bool
    {
        return MetaModule::getInstance()->optimizeMetadata->elementMetadataField->showSearchMeta;
    }

    public function getSettingsHtml(Field $field): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-meta/_components/fields/elementmetadata/blocks/search.twig', [
            'meta' => $this,
            'field' => $field,
        ]);
    }
}
