<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\uris\elementgroups\ElementGroup;
use BarrelStrength\Sprout\uris\elementgroups\ElementGroupInterface;
use BarrelStrength\Sprout\uris\UrisModule;
use Craft;
use craft\base\Element;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class SitemapMetadata extends Component
{
    public const NO_ELEMENT_TYPE = null;

    private array $_elementsWithUris = [];

    public function initElementsWithUris(): void
    {
        if ($this->_elementsWithUris) {
            return;
        }

        $elementTypes = UrisModule::getElementsWithUris();

        $elementTypesWithUris = array_filter($elementTypes, static function($elementType) {
            $element = new $elementType();
            $behaviors = $element->getBehaviors();

            return array_filter($behaviors, static function($behavior) {
                return $behavior instanceof ElementGroupInterface;
            });
        });

        foreach ($elementTypesWithUris as $elementTypeWithUri) {
            $element = new $elementTypeWithUri();
            $this->_elementsWithUris[$element::pluralLowerDisplayName()]
                = $element;
        }
    }

    /**
     * Get all registered Element Groups
     */
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

    public function saveSitemapMetadata(SitemapMetadataRecord $sitemapMetadata): bool
    {
        if ($sitemapMetadata->type === self::NO_ELEMENT_TYPE) {
            $sitemapMetadata->setScenario('customSection');
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

        // If aggregating by Site Group, copy URL-Enabled Sitemap rules to the whole group
        $this->copySitemapSectionRulesToAllSites($sitemapMetadata);

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

    /**
     * Returns all Custom Sitemap Sections
     */
    public function getCustomSitemapMetadata($siteId): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[type]]' => self::NO_ELEMENT_TYPE,
                '[[siteId]]' => $siteId,
            ])
            ->all();
    }

    public function getElementGroups(Element $element, $siteId): array
    {
        $sources = $element->defineElementGroups($siteId);

        $sitemapMetadataByKey = SitemapsMetadataHelper::getSitemapMetadataIndexedByElementGroupKey($element, $siteId);

        // Prepare a list of all Element Groups for this Element
        // if we have an existing Sitemap, use it, otherwise fallback to a new model
        $elementGroups = [];

        foreach ($sources as $source) {

            $elementGroup = new ElementGroup();
            $elementGroupKey = $element::pluralLowerDisplayName() . '-' . $source->id;

            if (isset($sitemapMetadataByKey[$elementGroupKey])) {

                // If an Element Group exists as Sitemap, use it
                $sitemapMetadata = $sitemapMetadataByKey[$elementGroupKey];
                $sitemapMetadata->id = $sitemapMetadataByKey[$elementGroupKey]->id;
            } else {
                // If no Element Group exists, create a new one
                $sitemapMetadata = new SitemapMetadataRecord();
                $sitemapMetadata->elementGroupId = $source->id;
            }

            $elementGroup->id = $source->id;
            $sitemapMetadata->name = $source->name;
            $sitemapMetadata->handle = $source->handle;

            $element->setElementGroupId($source);

            $sitemapMetadata->uri = $element->getUriFormat();

            $elementGroup->sitemapMetadata = $sitemapMetadata;

            $elementGroups[$elementGroupKey] = $elementGroup;
        }

        return $elementGroups;
    }

    public function uriHasTags($uri = null): bool
    {
        if (str_contains($uri, '{{')) {
            return true;
        }

        return str_contains($uri, '{%');
    }

    private function copySitemapSectionRulesToAllSites(SitemapMetadataRecord $sitemapMetadata): void
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

        // all sections saved for this site
        $sitemapMetadataRecords = SitemapMetadataRecord::find()
            ->where(['in', 'siteId', $siteIds])
            ->andWhere([
                'elementGroupId' => $sitemapMetadata->elementGroupId,
            ])
            ->indexBy('siteId')
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {

            $sitemapMetadataRecord = $sitemapMetadataRecords[$siteInGroup->id]
                ?? new SitemapMetadataRecord($sitemapMetadata->getAttributes());

            $sitemapMetadataRecord->siteId = $siteInGroup->id;
            $sitemapMetadataRecord->type = $sitemapMetadata->type;
            $sitemapMetadataRecord->elementGroupId = $sitemapMetadata->elementGroupId;
            $sitemapMetadataRecord->uri = $sitemapMetadata->uri;
            $sitemapMetadataRecord->priority = $sitemapMetadata->priority;
            $sitemapMetadataRecord->changeFrequency = $sitemapMetadata->changeFrequency;
            $sitemapMetadataRecord->enabled = $sitemapMetadata->enabled;

            $sitemapMetadataRecord->save();
        }
    }
}
