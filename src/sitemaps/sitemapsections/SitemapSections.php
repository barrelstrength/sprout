<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapsections;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\uris\components\sectiontypes\NoSectionSectionType;
use BarrelStrength\Sprout\uris\UrisModule;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSection;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSectionType;
use Craft;
use craft\base\Element;
use craft\errors\SiteNotFoundException;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

class SitemapSections extends Component
{
    public array $urlEnabledSectionTypes;

    protected SitemapSectionRecord $sitemapsRecord;

    /**
     * Returns all Custom Sitemap Sections
     */
    public function getCustomSitemapSections($siteId): array
    {
        return SitemapSectionRecord::find()
            ->where([
                '[[type]]' => NoSectionSectionType::class,
                '[[siteId]]' => $siteId,
            ])
            ->all();
    }

    /**
     * Get all Sitemap Sections related to this URL-Enabled Section Type
     *
     * Order the results by URL-Enabled Section ID: type-id
     * Example: entries-5, categories-12
     */
    public function getSitemapSections(UrlEnabledSectionType $urlEnabledSectionType, $siteId = null): array
    {
        $type = $urlEnabledSectionType::class;
        $allSitemapSections = SitemapsModule::getInstance()->sitemaps->getSitemapSectionsByType($type, $siteId);

        $sitemapSections = [];

        foreach ($allSitemapSections as $sitemapSection) {
            $urlEnabledSectionUniqueKey = $urlEnabledSectionType->getId() . '-' . $sitemapSection['urlEnabledSectionId'];

            $sitemapSections[$urlEnabledSectionUniqueKey] = $sitemapSection;
        }

        return $sitemapSections;
    }

    /**
     * Get all the Sitemap Sections of a particular type
     */
    public function getSitemapSectionsByType($type, $siteId = null): array
    {
        if ($siteId === null) {
            throw new SiteNotFoundException('Unable to find site. $siteId must not be null');
        }

        return SitemapSectionRecord::find()
            ->where([
                '[[type]]' => $type,
                '[[siteId]]' => $siteId,
            ])
            ->all();
    }

    public function getSitemapSectionById($id): SitemapSectionRecord|ActiveRecord|null
    {
        return SitemapSectionRecord::find()
            ->where([
                'id' => $id,
            ])
            ->one();
    }

    public function saveSitemapSection(SitemapSectionRecord $sitemapSection): bool
    {
        if ($sitemapSection->type === NoSectionSectionType::class) {
            $sitemapSection->setScenario('customSection');
        }

        if (!$sitemapSection->save(true)) {
            return false;
        }

        // Custom Sections will be allowed to be unique, even in Multi-Lingual Sitemaps
        if ($sitemapSection->type === NoSectionSectionType::class) {
            return true;
        }

        $settings = SitemapsModule::getInstance()->getSettings();

        // If we don't have a multi-site install or are just aggregating by site, we're done.
        if (!$settings->aggregateBySiteGroup()) {
            return true;
        }

        // If aggregating by Site Group, copy URL-Enabled Sitemap rules to the whole group
        $this->copySitemapSectionRulesToAllSites($sitemapSection);

        return true;
    }

    public function deleteSitemapSectionById($id = null): bool
    {
        $sitemapSectionRecord = SitemapSectionRecord::findOne($id);

        if (!$sitemapSectionRecord instanceof SitemapSectionRecord) {
            return false;
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(SproutTable::SITEMAPS, [
                'id' => $id,
            ])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Get all registered Element Groups
     */
    public function getUrlEnabledSectionTypesForSitemaps($siteId = null): array
    {
        $this->prepareUrlEnabledSectionTypesForSitemaps($siteId);

        return $this->urlEnabledSectionTypes;
    }

    /**
     * Prepare the $this->urlEnabledSectionTypes variable for use in Sections and Sitemap pages
     */
    public function prepareUrlEnabledSectionTypesForSitemaps($siteId = null): void
    {
        // Have we already prepared our URL-Enabled Sections?
        if (!empty($this->urlEnabledSectionTypes)) {
            return;
        }

        $registeredUrlEnabledSectionsTypes = UrisModule::getInstance()->urlEnabledSections->getRegisteredUrlEnabledSectionsEvent();

        foreach ($registeredUrlEnabledSectionsTypes as $urlEnabledSectionType) {
            /**
             * @var UrlEnabledSectionType $urlEnabledSectionType
             */
            $urlEnabledSectionType = new $urlEnabledSectionType();
            $sitemapSections = SitemapsModule::getInstance()->sitemaps->getSitemapSections($urlEnabledSectionType, $siteId);
            $allUrlEnabledSections = $urlEnabledSectionType->getAllUrlEnabledSections($siteId);

            // Prepare a list of all URL-Enabled Sections for this URL-Enabled Section Type
            // if we have an existing Sitemap, use it, otherwise fallback to a new model
            $urlEnabledSections = [];

            /**
             * @var UrlEnabledSection $urlEnabledSection
             */
            foreach ($allUrlEnabledSections as $urlEnabledSection) {
                $uniqueKey = $urlEnabledSectionType->getId() . '-' . $urlEnabledSection->id;

                $model = new UrlEnabledSection();

                if (isset($sitemapSections[$uniqueKey])) {
                    // If an URL-Enabled Section exists as Sitemap, use it
                    $sitemapSection = $sitemapSections[$uniqueKey];
                    $sitemapSection->id = $sitemapSections[$uniqueKey]->id;
                } else {
                    // If no URL-Enabled Section exists, create a new one
                    $sitemapSection = new SitemapSectionRecord();
                    $sitemapSection->urlEnabledSectionId = $urlEnabledSection->id;
                }

                $model->type = $urlEnabledSectionType;
                $model->id = $urlEnabledSection->id;

                $sitemapSection->name = $urlEnabledSection->name;
                $sitemapSection->handle = $urlEnabledSection->handle;
                $sitemapSection->uri = $model->getUrlFormat();

                $model->sitemapSection = $sitemapSection;

                $urlEnabledSections[$uniqueKey] = $model;
            }

            $urlEnabledSectionType->urlEnabledSections = $urlEnabledSections;

            $this->urlEnabledSectionTypes[$urlEnabledSectionType->getId()] = $urlEnabledSectionType;
        }
    }

    public function getElementViaContext($context): ?Element
    {
        $currentSite = Craft::$app->sites->getCurrentSite();

        $this->prepareUrlEnabledSectionTypesForSitemaps($currentSite->id);

        foreach ($this->urlEnabledSectionTypes as $urlEnabledSectionType) {
            $matchedElementVariable = $urlEnabledSectionType->getMatchedElementVariable();

            if (isset($context[$matchedElementVariable])) {
                return $context[$matchedElementVariable];
            }
        }

        return null;
    }

    public function getUrlEnabledSectionTypeByType($type): array|UrlEnabledSectionType
    {
        $currentSite = Craft::$app->sites->getCurrentSite();

        $this->prepareUrlEnabledSectionTypesForSitemaps($currentSite->id);

        foreach ($this->urlEnabledSectionTypes as $urlEnabledSectionType) {
            if ($urlEnabledSectionType::class == $type) {
                return $urlEnabledSectionType;
            }
        }

        return [];
    }

    public function uriHasTags($uri = null): bool
    {
        if (str_contains($uri, '{{')) {
            return true;
        }

        return str_contains($uri, '{%');
    }

    private function copySitemapSectionRulesToAllSites(SitemapSectionRecord $sitemapSection): void
    {
        $site = Craft::$app->getSites()->getSiteById($sitemapSection->siteId);

        if (!$site) {
            throw new NotFoundHttpException('Unable to find Site with ID: ' . $sitemapSection->siteId);
        }

        $sitesInGroup = Craft::$app->getSites()->getSitesByGroupId($site->groupId);

        $siteIds = [];

        foreach ($sitesInGroup as $siteInGroup) {
            $siteIds[] = $siteInGroup->id;
        }

        // all sections saved for this site
        $sitemapSectionRecords = SitemapSectionRecord::find()
            ->where(['in', 'siteId', $siteIds])
            ->andWhere([
                'urlEnabledSectionId' => $sitemapSection->urlEnabledSectionId,
            ])
            ->indexBy('siteId')
            ->all();

        foreach ($sitesInGroup as $siteInGroup) {

            $sitemapSectionRecord = $sitemapSectionRecords[$siteInGroup->id]
                ?? new SitemapSectionRecord($sitemapSection->getAttributes());

            $sitemapSectionRecord->siteId = $siteInGroup->id;
            $sitemapSectionRecord->type = $sitemapSection->type;
            $sitemapSectionRecord->urlEnabledSectionId = $sitemapSection->urlEnabledSectionId;
            $sitemapSectionRecord->uri = $sitemapSection->uri;
            $sitemapSectionRecord->priority = $sitemapSection->priority;
            $sitemapSectionRecord->changeFrequency = $sitemapSection->changeFrequency;
            $sitemapSectionRecord->enabled = $sitemapSection->enabled;

            $sitemapSectionRecord->save();
        }
    }
}
