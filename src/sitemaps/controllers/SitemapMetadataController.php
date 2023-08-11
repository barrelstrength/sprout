<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\sitemaps\SitemapsSettings;
use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use yii\db\ActiveRecord;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SitemapMetadataController extends Controller
{
    /**
     * Renders the Sitemap Index Page
     */
    public function actionSitemapMetadataIndexTemplate(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $settings = SitemapsModule::getInstance()->getSettings();
        $isMultiSite = Craft::$app->getIsMultiSite();
        $isAggregationMethodMultiLanguage = $settings->sitemapAggregationMethod === SitemapsSettings::AGGREGATION_METHOD_MULTI_LINGUAL;

        // Get Enabled Site IDs. Remove any disabled IDS.
        $enabledSiteIds = array_filter($settings->siteSettings);
        $enabledSiteGroupIds = array_filter($settings->groupSettings);

        $missingSettingsScenario1 = !$isMultiSite && empty($enabledSiteIds);
        $missingSettingsScenario2 = $isMultiSite
            && !$isAggregationMethodMultiLanguage
            && empty($enabledSiteGroupIds);

        if ($missingSettingsScenario1 && $missingSettingsScenario2) {
            throw new NotFoundHttpException('No Sites are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site for your Sitemap.');
        }

        $missingSettingsScenario3 = $isMultiSite
            && $isAggregationMethodMultiLanguage
            && empty($enabledSiteGroupIds);

        if ($missingSettingsScenario3) {
            throw new NotFoundHttpException('No Site Groups are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site Group for your Sitemap.');
        }

        // Get all Editable Sites for this user that also have editable Sitemaps
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // For per-site sitemaps, only display the Sites enabled in the Sprout SEO settings
        if ($isAggregationMethodMultiLanguage) {
            $siteIdsFromEditableGroups = [];

            foreach ($enabledSiteGroupIds as $enabledSiteGroupId) {
                $enabledSitesInGroup = Craft::$app->sites->getSitesByGroupId($enabledSiteGroupId);
                foreach ($enabledSitesInGroup as $enabledSites) {
                    $siteIdsFromEditableGroups[] = (int)$enabledSites->id;
                }
            }

            $editableSiteIds = array_intersect($siteIdsFromEditableGroups, $editableSiteIds);
        } else {
            $editableSiteIds = array_intersect($enabledSiteIds, $editableSiteIds);
        }

        if ($isMultiSite) {
            // For Multi-Site we have to figure out which Site and Site Group matter
            $currentSiteGroup = Craft::$app->sites->getGroupById($site->groupId);

            if (!$currentSiteGroup) {
                throw new NotFoundHttpException('Site group not found.');
            }

            $sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);
            $firstSiteInGroup = $sitesInCurrentSiteGroup[0];
        } else {
            // For a single site, the primary site ID will do
            $firstSiteInGroup = $site;
        }

        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $elementsWithUris = $sitemapsService->getElementWithUris();
        $sitemapMetadataByKey = $sitemapsService->getContentSitemapMetadata($site);
        $contentQueries = $sitemapsService->getContentQuerySitemapMetadata($site->id);
        $customPages = $sitemapsService->getCustomPagesSitemapMetadata($site->id);

        $elementsToDisplayForSitemaps = array_column($sitemapMetadataByKey, 'type');
        $elementsWithUris = array_filter($elementsWithUris,
            static function($element) use ($elementsToDisplayForSitemaps) {
                return in_array($element::class, $elementsToDisplayForSitemaps, true);
            });

        return $this->renderTemplate('sprout-module-sitemaps/_sitemapmetadata/index.twig', [
            'title' => Craft::t('sprout-module-sitemaps', 'Sitemaps'),
            'site' => $site,
            'firstSiteInGroup' => $firstSiteInGroup,
            'editableSiteIds' => $editableSiteIds,
            'elementsWithUris' => $elementsWithUris,
            'sitemapMetadataByKey' => $sitemapMetadataByKey,
            'contentQueries' => $contentQueries,
            'customPages' => $customPages,
            'settings' => $settings,
            'DEFAULT_PRIORITY' => SitemapsSettings::DEFAULT_PRIORITY,
            'DEFAULT_CHANGE_FREQUENCY' => SitemapsSettings::DEFAULT_CHANGE_FREQUENCY,
        ]);
    }

    /**
     * Renders a Sitemap Edit Page
     */
    public function actionSitemapMetadataCustomQueryEditTemplate(string $sourceKey, int $sitemapMetadataId = null, SitemapMetadataRecord $sitemapMetadata = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // Make sure the user has permission to edit that site
        if (!in_array($site->id, $editableSiteIds, false)) {
            throw new ForbiddenHttpException('User not permitted to edit content for this site.');
        }

        if (!$sitemapMetadata instanceof ActiveRecord) {
            if ($sitemapMetadataId) {
                $sitemapMetadata = SitemapsModule::getInstance()->sitemaps->getSitemapMetadataById($sitemapMetadataId);
            } else {
                $sitemapMetadata = new SitemapMetadataRecord();
                $sitemapMetadata->siteId = $site->id;
            }
        }

        $continueEditingUrl = UrlHelper::cpUrl('sprout/sitemaps/edit/' . $sourceKey . '/{id}');

        if ($sourceKey === SitemapKey::CUSTOM_QUERY) {

            if ($sitemapMetadata->settings) {
                $currentConditionRules = Json::decodeIfJson($sitemapMetadata->settings);
                $currentCondition = Craft::$app->conditions->createCondition($currentConditionRules);
            } else {
                $currentCondition = null;
            }

            $sitemapsService = SitemapsModule::getInstance()->sitemaps;
            $elementsWithUris = $sitemapsService->getElementWithUris();

            $settingsHtml = '';

            /** @var  Element $element */
            foreach ($elementsWithUris as $element) {
                $elementOptions[] = [
                    'label' => $element::displayName(),
                    'value' => $element::class,
                ];

                if ($currentCondition && $currentCondition->elementType === $element::class) {
                    $condition = $currentCondition;
                } else {
                    $condition = $element::createCondition();
                    $condition->elementType = $element::class;
                }

                $condition->sortable = true;
                $condition->mainTag = 'div';
                $condition->name = $element::lowerDisplayName() . '-conditionRules';
                $condition->id = $element::lowerDisplayName() . '-conditionRules';

                $settingsHtml .= Html::tag('div', $condition->getBuilderHtml(), [
                    'id' => 'element-type-' . Html::id($element::class),
                    'class' => 'hidden',
                ]);
            }

            $conditionBuilderSettingsHtml = Cp::fieldHtml($settingsHtml, [
                'label' => Craft::t('sprout-module-sitemaps', 'Content Query'),
                'instructions' => Craft::t('sprout-module-sitemaps', 'Add URLs to the sitemap that match the following rules:'),
            ]);
        }

        return $this->renderTemplate('sprout-module-sitemaps/_sitemapmetadata/edit.twig', [
            'site' => $site,
            'sitemapMetadata' => $sitemapMetadata,
            'continueEditingUrl' => $continueEditingUrl,
            'sourceKey' => $sourceKey,
            'elementOptions' => $elementOptions ?? [],
            'conditionBuilderSettingsHtml' => $conditionBuilderSettingsHtml ?? '',
            'currentCondition' => $currentCondition ?? null,
        ]);
    }

    public function actionSaveSitemapMetadata(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $sitemapMetadataId = Craft::$app->getRequest()->getBodyParam('sitemapMetadataId');

        $sitemapMetadataRecord = SitemapMetadataRecord::findOne($sitemapMetadataId);

        if ($sitemapMetadataRecord === null) {
            $sitemapMetadataRecord = new SitemapMetadataRecord();
        }

        $request = Craft::$app->getRequest();

        $type = $request->getBodyParam('type');
        $sourceKey = $request->getBodyParam('sourceKey');

        $sitemapMetadataRecord->siteId = $request->getBodyParam('siteId');
        $sitemapMetadataRecord->sourceKey = $sourceKey;
        $sitemapMetadataRecord->type = $type;
        $sitemapMetadataRecord->uri = $request->getBodyParam('uri', $sitemapMetadataRecord->uri);
        $sitemapMetadataRecord->priority = $request->getBodyParam('priority');
        $sitemapMetadataRecord->changeFrequency = $request->getBodyParam('changeFrequency');
        $sitemapMetadataRecord->description = $request->getBodyParam('description', $sitemapMetadataRecord->description);
        $sitemapMetadataRecord->enabled = (bool)$request->getBodyParam('enabled');

        if ($sourceKey === SitemapKey::CUSTOM_QUERY) {
            $conditionBuilderParam = $type::lowerDisplayName() . '-conditionRules';
            $condition = $request->getBodyParam($conditionBuilderParam);
            $sitemapMetadataRecord->settings = $condition;
        }

        if (!SitemapsModule::getInstance()->sitemaps->saveSitemapMetadata($sitemapMetadataRecord)) {
            if (Craft::$app->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $sitemapMetadataRecord->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(
                Craft::t('sprout-module-sitemaps', "Couldn't save the Sitemap.")
            );

            Craft::$app->getUrlManager()->setRouteParams([
                'sitemapMetadata' => $sitemapMetadataRecord,
            ]);

            return null;
        }

        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'sitemapMetadata' => $sitemapMetadataRecord,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-sitemaps', 'Sitemap saved.'));

        return $this->redirectToPostedUrl($sitemapMetadataRecord);
    }

    public function actionDeleteSitemapMetadataById(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $sitemapMetadataId = Craft::$app->getRequest()->getRequiredBodyParam('sitemapMetadataId');

        $result = SitemapsModule::getInstance()->sitemaps->deleteSitemapMetadataById($sitemapMetadataId);

        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $result >= 0,
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
