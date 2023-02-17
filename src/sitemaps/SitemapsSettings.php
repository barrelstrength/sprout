<?php

namespace BarrelStrength\Sprout\sitemaps;

use Craft;
use craft\config\BaseConfig;

class SitemapsSettings extends BaseConfig
{
    public const DEFAULT_PRIORITY = 0.5;

    public const DEFAULT_CHANGE_FREQUENCY = 'weekly';

    public const AGGREGATION_METHOD_SINGLE_LANGUAGE = 'singleLanguageSitemaps';

    public const AGGREGATION_METHOD_MULTI_LINGUAL = 'multiLingualSitemaps';

    public bool $enableCustomSections = false;

    public int $totalElementsPerSitemap = 500;

    public string $sitemapAggregationMethod = self::AGGREGATION_METHOD_SINGLE_LANGUAGE;

    public array $siteSettings = [];

    public array $groupSettings = [];

    public function init(): void
    {
        if (empty($this->siteSettings)) {
            $site = Craft::$app->getSites()->getPrimarySite();
            $this->siteSettings[$site->id] = $site->id;
        }

        parent::init();
    }

    public function enableCustomSections(bool $value): self
    {
        $this->enableCustomSections = $value;

        return $this;
    }

    public function totalElementsPerSitemap(int $value): self
    {
        $this->totalElementsPerSitemap = $value;

        return $this;
    }

    public function sitemapAggregationMethod(string $value): self
    {
        $this->sitemapAggregationMethod = $value;

        return $this;
    }

    public function siteSettings(array $value): self
    {
        $this->siteSettings = $value;

        return $this;
    }

    public function groupSettings(array $value): self
    {
        $this->groupSettings = $value;

        return $this;
    }

    public function aggregateBySiteGroup(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return false;
        }

        return $this->sitemapAggregationMethod === self::AGGREGATION_METHOD_MULTI_LINGUAL;
    }
}

