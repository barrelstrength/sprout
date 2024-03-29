<?php

namespace BarrelStrength\Sprout\sitemaps;

use Craft;
use craft\config\BaseConfig;
use craft\db\Query;
use craft\db\Table;

class SitemapsSettings extends BaseConfig
{
    public const DEFAULT_PRIORITY = 0.5;

    public const DEFAULT_CHANGE_FREQUENCY = 'weekly';

    public const AGGREGATION_METHOD_SINGLE_LANGUAGE = 'singleLanguageSitemaps';

    public const AGGREGATION_METHOD_MULTI_LINGUAL = 'multiLingualSitemaps';

    public bool $enableContentQuerySitemaps = false;

    public bool $enableCustomPagesSitemap = false;

    public int $totalElementsPerSitemap = 500;

    public string $sitemapAggregationMethod = self::AGGREGATION_METHOD_SINGLE_LANGUAGE;

    public array $siteSettings = [];

    public array $groupSettings = [];

    public function init(): void
    {
        if (empty($this->siteSettings)) {
            $site = Craft::$app->getSites()->getPrimarySite();
            $this->siteSettings[$site->uid] = $site->uid;
        }

        parent::init();
    }

    public function enableContentQuerySitemaps(bool $value): self
    {
        $this->enableContentQuerySitemaps = $value;

        return $this;
    }

    public function enableCustomPagesSitemap(bool $value): self
    {
        $this->enableCustomPagesSitemap = $value;

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

    public function getEnabledSiteIds(): array
    {
        if (!$enabledSiteUids = array_keys(array_filter($this->siteSettings))) {
            return [];
        }

        $ids = (new Query())
            ->select(['id'])
            ->from(Table::SITES)
            ->where(['in', 'uid', $enabledSiteUids])
            ->indexBy('uid')
            ->column();

        return array_map('intval', $ids);
    }

    public function getEnabledGroupIds(): array
    {
        if (!$enabledGroupUids = array_keys(array_filter($this->groupSettings))) {
            return [];
        }

        $ids = (new Query())
            ->select(['id'])
            ->from(Table::SITEGROUPS)
            ->where(['in', 'uid', $enabledGroupUids])
            ->indexBy('uid')
            ->column();

        return array_map('intval', $ids);
    }
}
