<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\CategorySitemapMetadata;
use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\EntrySitemapMetadata;
use BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\ProductSitemapMetadata;
use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\Category;
use craft\elements\Entry;
use craft\models\Site;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

class SitemapMetadata extends Component
{
    public const EVENT_REGISTER_ELEMENT_SITEMAP_METADATA = 'registerSproutElementSitemapMetadata';

    public const NO_ELEMENT_TYPE = null;

    private array $_elementsWithUris = [];

    public function getSitemapMetadataTypes(): array
    {
        $metadataRules = [
            Entry::class => EntrySitemapMetadata::class,
            Category::class => CategorySitemapMetadata::class,
        ];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $metadataRules[Product::class] = ProductSitemapMetadata::class;
        }

        $event = new RegisterElementSitemapMetadataEvent([
            'metadataRules' => $metadataRules,
        ]);

        $this->trigger(self::EVENT_REGISTER_ELEMENT_SITEMAP_METADATA, $event);

        return $event->metadataRules;
    }

    public function getSourceDetails(Site $site): array
    {
        $sitemapMetadataTypes = $this->getSitemapMetadataTypes();

        $sourceDetails = [];

        foreach ($sitemapMetadataTypes as $sitemapMetadataIntegration) {
            foreach ($sitemapMetadataIntegration::getSourceDetails($site) as $sourceKey => $sourceDetail) {
                $sourceDetails[$sourceKey] = $sourceDetail;
            }
        }

        return $sourceDetails;
    }

    public static function getElementTypesWithUris(): array
    {
        /** @var Element[] $types */
        $types = Craft::$app->getElements()->getAllElementTypes();

        $uriTypes = [];

        foreach ($types as $type) {
            if (!$type::hasUris()) {
                continue;
            }

            $uriTypes[] = $type;
        }

        return $uriTypes;
    }

    public function initElementsWithUris(): void
    {
        if ($this->_elementsWithUris) {
            return;
        }

        $elementTypes = self::getElementTypesWithUris();
        $sitemapMetadataTypes = SitemapsModule::getInstance()->sitemaps->getSitemapMetadataTypes();

        $elementTypesWithUris = array_filter($elementTypes, static function($elementType) use ($sitemapMetadataTypes) {
            return array_key_exists($elementType, $sitemapMetadataTypes);
        });

        foreach ($elementTypesWithUris as $elementTypeWithUri) {
            $element = new $elementTypeWithUri();
            $this->_elementsWithUris[$element::pluralLowerDisplayName()]
                = $element;
        }
    }

    public function getElementWithUris(): array
    {
        $this->initElementsWithUris();

        return $this->_elementsWithUris;
    }

    public function getElementWithUriByType($type): ?Element
    {
        $this->initElementsWithUris();

        foreach ($this->_elementsWithUris as $elementWithUri) {
            if ($elementWithUri::class !== $type) {
                continue;
            }

            return $elementWithUri;
        }

        return null;
    }

    public function getSitemapMetadataById($id): SitemapMetadataRecord|ActiveRecord|null
    {
        return SitemapMetadataRecord::find()
            ->where([
                'id' => $id,
            ])
            ->one();
    }

    /**
     * Get Sitemap Metadata related to all Element Groups
     *
     * Index results by Element Group ID: type-id
     * Example: entries-5, categories-12
     */
    public function getSitemapMetadataByKey(Site $site): array
    {
        $sourceDetails = $this->getSourceDetails($site);

        $sitemapMetadataRecords = SitemapMetadataRecord::find()
            ->where(['[[siteId]]' => $site->id])
            ->andWhere(['not', ['[[type]]' => self::NO_ELEMENT_TYPE]])
            ->indexBy('sourceKey')
            ->all();

        $sitemapMetadata = [];

        foreach ($sourceDetails as $sourceKey => $sourceDetail) {

            $record = $sitemapMetadataRecords[$sourceKey] ?? new SitemapMetadataRecord();

            $record->type = $sourceDetail['type'] ?? null;
            $record->name = $sourceDetail['name'] ?? null;
            $record->uri = $sourceDetail['urlPattern'] ?? null;

            $sitemapMetadata[$sourceKey] = $record;
        }

        return $sitemapMetadata;
    }

    public function getSitemapPagesMetadata($siteId): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[type]]' => self::NO_ELEMENT_TYPE,
                '[[siteId]]' => $siteId,
            ])
            ->all();
    }

    public function saveSitemapMetadata(SitemapMetadataRecord $sitemapMetadata): bool
    {
        if ($sitemapMetadata->type === self::NO_ELEMENT_TYPE) {

            $sitemapMetadata->setScenario('customSection');
        } else {
            // No need to store URI for Element SitemapMetadata
            $sitemapMetadata->uri = null;
        }

        if (!$sitemapMetadata->save(true)) {
            return false;
        }

        // Custom Sections will be allowed to be unique, even in Multi-Lingual Sitemaps
        if ($sitemapMetadata->type === self::NO_ELEMENT_TYPE) {
            return true;
        }

        $settings = SitemapsModule::getInstance()->getSettings();

        // If no multi-site install or aggregating by site, we're done
        if (!$settings->aggregateBySiteGroup()) {
            return true;
        }

        // If aggregating by Site Group, copy Sitemap Metadata to the whole group
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
            if ($siteInGroup->id === (int)$sitemapMetadata->siteId) {
                continue;
            }

            $siteIds[] = $siteInGroup->id;
        }

        if (empty($siteIds)) {
            return;
        }

        // all sections saved for this site
        $sitemapMetadataRecords = SitemapMetadataRecord::find()
            ->where(['in', 'siteId', $siteIds])
            ->andWhere(['sourceKey' => $sitemapMetadata->sourceKey])
            ->indexBy('siteId')
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {

            $sitemapMetadataRecord = $sitemapMetadataRecords[$siteInGroup->id]
                ?? new SitemapMetadataRecord($sitemapMetadata->getAttributes());

            $sitemapMetadataRecord->siteId = $siteInGroup->id;
            $sitemapMetadataRecord->sourceKey = $sitemapMetadata->sourceKey;
            $sitemapMetadataRecord->type = $sitemapMetadata->type;
            $sitemapMetadataRecord->uri = $sitemapMetadata->uri;
            $sitemapMetadataRecord->priority = $sitemapMetadata->priority;
            $sitemapMetadataRecord->changeFrequency = $sitemapMetadata->changeFrequency;
            $sitemapMetadataRecord->enabled = $sitemapMetadata->enabled;

            $sitemapMetadataRecord->save();
        }
    }
}
