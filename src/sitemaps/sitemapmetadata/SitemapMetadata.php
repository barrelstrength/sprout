<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\CategorySitemapMetadata;
use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\EntrySitemapMetadata;
use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\ProductSitemapMetadata;
use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\commerce\elements\Product;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class SitemapMetadata extends Component
{
    public const EVENT_REGISTER_ELEMENT_SITEMAP_METADATA = 'registerSproutElementSitemapMetadata';

    private array $_elementsWithUris = [];

    public function getSitemapMetadataTypes(): array
    {
        $defaultMetadataTypes = [
            Entry::class => EntrySitemapMetadata::class,
            Category::class => CategorySitemapMetadata::class,
        ];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $defaultMetadataTypes[Product::class] = ProductSitemapMetadata::class;
        }

        $proEvent = new RegisterComponentTypesEvent([
            'types' => $defaultMetadataTypes,
        ]);

        if (SitemapsModule::isPro()) {
            $this->trigger(self::EVENT_REGISTER_ELEMENT_SITEMAP_METADATA, $proEvent);

            return $proEvent->types;
        }
        
        return $defaultMetadataTypes;

    }

    public function getElementWithUris(): array
    {
        if ($this->_elementsWithUris) {
            return $this->_elementsWithUris;
        }

        $elementTypes = ElementUriHelper::getElementTypesWithUris();
        $sitemapMetadataTypes = $this->getSitemapMetadataTypes();

        $elementTypesWithUris = array_filter($elementTypes, static function($elementType) use ($sitemapMetadataTypes) {
            return array_key_exists($elementType, $sitemapMetadataTypes);
        });

        foreach ($elementTypesWithUris as $elementTypeWithUri) {
            $element = new $elementTypeWithUri();
            $this->_elementsWithUris[$element::class]
                = $element;
        }

        return $this->_elementsWithUris;
    }

    public function saveSitemapMetadata(SitemapMetadataRecord $sitemapMetadata): bool
    {
        if ($sitemapMetadata->sourceKey === SitemapKey::CUSTOM_PAGES) {
            $sitemapMetadata->setScenario(SitemapMetadataRecord::SCENARIO_CUSTOM_PAGES);
        } elseif ($sitemapMetadata->sourceKey === SitemapKey::CUSTOM_QUERY) {
            $sitemapMetadata->setScenario(SitemapMetadataRecord::SCENARIO_CUSTOM_QUERY);
        } else {
            // No need to store URI for Element SitemapMetadata
            $sitemapMetadata->uri = null;
        }

        if (!$sitemapMetadata->save(true)) {
            return false;
        }

        // Custom Sections and Custom Queries are unique per-site, even in Multi-Lingual Sitemaps
        if (in_array($sitemapMetadata->sourceKey, [SitemapKey::CUSTOM_PAGES, SitemapKey::CUSTOM_QUERY], true)) {
            return true;
        }

        $settings = SitemapsModule::getInstance()->getSettings();

        // If no multi-site install or aggregating by site, we're done
        if (!$settings->aggregateBySiteGroup()) {
            return true;
        }

        // If aggregating by Site Group, copy Content Sitemap Metadata to the whole group
        $this->copySitemapMetadataToAllSitesInGroup($sitemapMetadata);

        return true;
    }

    public function deleteSitemapMetadataById($id = null): bool
    {
        $sitemapMetadataRecord = SitemapMetadataRecord::findOne($id);

        if (!$sitemapMetadataRecord instanceof SitemapMetadataRecord) {
            return false;
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(SproutTable::SITEMAPS_METADATA, [
                'id' => $id,
            ])
            ->execute();

        return (bool)$affectedRows;
    }

    // used in templates
    public function uriHasTags($uri = null): bool
    {
        if (str_contains($uri, '{{')) {
            return true;
        }

        return str_contains($uri, '{%');
    }

    private function copySitemapMetadataToAllSitesInGroup(SitemapMetadataRecord $sitemapMetadata): void
    {
        $site = Craft::$app->getSites()->getSiteById($sitemapMetadata->siteId);

        if (!$site) {
            throw new NotFoundHttpException('Unable to find Site with ID: ' . $sitemapMetadata->siteId);
        }

        $sitesInGroup = Craft::$app->getSites()->getSitesByGroupId($site->groupId);

        $siteIds = [];

        foreach ($sitesInGroup as $siteInGroup) {
            $siteIds[] = $siteInGroup->id;
        }

        if (empty($siteIds)) {
            return;
        }

        $sitemapMetadataRecords = SitemapMetadataRecord::find()
            ->where(['in', 'siteId', $siteIds])
            ->andWhere(['sourceKey' => $sitemapMetadata->sourceKey])
            ->indexBy('siteId')
            ->all();

        foreach ($siteIds as $siteId) {

            if (isset($sitemapMetadataRecords[$siteId])) {
                $sitemapMetadataRecord = $sitemapMetadataRecords[$siteId];
            } else {
                $sitemapMetadataRecord = new SitemapMetadataRecord();
                $sitemapMetadataRecord->sourceKey = $sitemapMetadata->sourceKey;
            }

            $sitemapMetadataRecord->siteId = $siteId;
            $sitemapMetadataRecord->type = $sitemapMetadata->type;
            $sitemapMetadataRecord->uri = $sitemapMetadata->uri;
            $sitemapMetadataRecord->priority = $sitemapMetadata->priority;
            $sitemapMetadataRecord->changeFrequency = $sitemapMetadata->changeFrequency;
            $sitemapMetadataRecord->enabled = $sitemapMetadata->enabled;

            $sitemapMetadataRecord->save();
        }
    }
}
