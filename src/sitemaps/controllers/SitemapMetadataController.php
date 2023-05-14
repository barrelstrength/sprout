<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadata;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\helpers\Cp;
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
    public function actionSitemapIndexTemplate(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $settings = SitemapsModule::getInstance()->getSettings();
        $isMultiSite = Craft::$app->getIsMultiSite();

        // Get Enabled Site IDs. Remove any disabled IDS.
        $enabledSiteIds = array_filter($settings->siteSettings);
        $enabledSiteGroupIds = array_filter($settings->groupSettings);

        if (!$isMultiSite && empty($enabledSiteIds)) {
            throw new NotFoundHttpException('No Sites are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site for your Sitemap.');
        }

        if ($isMultiSite && empty($enabledSiteGroupIds)) {
            throw new NotFoundHttpException('No Site Groups are enabled for your Sitemap. Check your Craft Sites settings and Sprout SEO Sitemap Settings to enable a Site Group for your Sitemap.');
        }

        // Get all Editable Sites for this user that also have editable Sitemaps
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // For per-site sitemaps, only display the Sites enabled in the Sprout SEO settings
        if ($isMultiSite === false) {

            $editableSiteIds = array_intersect($enabledSiteIds, $editableSiteIds);
        } else {
            $siteIdsFromEditableGroups = [];

            foreach ($enabledSiteGroupIds as $enabledSiteGroupId) {
                $enabledSitesInGroup = Craft::$app->sites->getSitesByGroupId($enabledSiteGroupId);
                foreach ($enabledSitesInGroup as $enabledSites) {
                    $siteIdsFromEditableGroups[] = (int)$enabledSites->id;
                }
            }

            $editableSiteIds = array_intersect($siteIdsFromEditableGroups, $editableSiteIds);
        }

        if ($isMultiSite) {
            // Form Multi-Site we have to figure out which Site and Site Group matter
            //if ($siteHandle !== null) {

            //If we have a handle, the Current Site and First Site in Group may be different
            //$currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            //if (!$currentSite) {
            //    throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            //}

            $currentSiteGroup = Craft::$app->sites->getGroupById($site->groupId);

            if (!$currentSiteGroup) {
                throw new NotFoundHttpException('Site group not found.');
            }

            $sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);
            $firstSiteInGroup = $sitesInCurrentSiteGroup[0];
            //} else {
            // If we don't have a handle, we'll load the first site in the first group
            // We assume at least one site group and the Current Site will be the same as the First Site

            //$allSiteGroups = Craft::$app->sites->getAllGroups();
            //$currentSiteGroup = $allSiteGroups[0];
            //$sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);
            //$firstSiteInGroup = $sitesInCurrentSiteGroup[0];
            //$currentSite = $firstSiteInGroup;
            //}
        } else {
            // For a single site, the primary site ID will do
            //$currentSite = Craft::$app->getSites()->getPrimarySite();
            $firstSiteInGroup = $site;
        }

        $sitemapsService = SitemapsModule::getInstance()->sitemaps;

        $elementsWithUris = $sitemapsService->getElementWithUris();
        $sitemapMetadataByKey = $sitemapsService->getSitemapMetadataByKey($site);
        $customSections = $sitemapsService->getSitemapPagesMetadata($site->id);

        return $this->renderTemplate('sprout-module-sitemaps/_sitemapmetadata/index', [
            'title' => Craft::t('sprout-module-sitemaps', 'Sitemaps'),
            'currentSite' => $site,
            'firstSiteInGroup' => $firstSiteInGroup,
            'editableSiteIds' => $editableSiteIds,
            'elementsWithUris' => $elementsWithUris,
            'sitemapMetadataByKey' => $sitemapMetadataByKey,
            'customSections' => $customSections,
            'settings' => $settings,
        ]);
    }

    /**
     * Renders a Sitemap Edit Page
     */
    public function actionSitemapEditTemplate(int $sitemapMetadataId = null, SitemapMetadataRecord $sitemapMetadataRecord = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        //$siteHandle = Craft::$app->getRequest()->getRequiredQueryParam('site');
        //
        //if ($siteHandle === null) {
        //    throw new NotFoundHttpException('Unable to find site with handle: ' . $siteHandle);
        //}

        //$currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // Make sure the user has permission to edit that site
        if (!in_array($site->id, $editableSiteIds, false)) {
            throw new ForbiddenHttpException('User not permitted to edit content for this site.');
        }

        if (!$sitemapMetadataRecord instanceof ActiveRecord) {
            if ($sitemapMetadataId) {
                $sitemapMetadataRecord = SitemapsModule::getInstance()->sitemaps->getSitemapMetadataById($sitemapMetadataId);
            } else {
                $sitemapMetadataRecord = new SitemapMetadataRecord();
                $sitemapMetadataRecord->siteId = $site->id;
                $sitemapMetadataRecord->type = SitemapMetadata::NO_ELEMENT_TYPE;
            }
        }

        $continueEditingUrl = 'sprout/sitemaps/edit/{id}?site=' . $site->handle;

        $tabs = [
            [
                'label' => 'Custom Page',
                'url' => '#tab1',
                'class' => null,
            ],
        ];

        return $this->renderTemplate('sprout-module-sitemaps/_sitemapmetadata/edit', [
            'currentSite' => $site,
            'sitemapMetadata' => $sitemapMetadataRecord,
            'continueEditingUrl' => $continueEditingUrl,
            'tabs' => $tabs,
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
        $type = empty($type) ? SitemapMetadata::NO_ELEMENT_TYPE : $type;

        $sitemapMetadataRecord->siteId = $request->getBodyParam('siteId');
        $sitemapMetadataRecord->sourceKey = $request->getBodyParam('sourceKey');
        $sitemapMetadataRecord->elementGroupId = $request->getBodyParam('elementGroupId');
        $sitemapMetadataRecord->uri = $request->getBodyParam('uri');
        $sitemapMetadataRecord->type = $type;
        $sitemapMetadataRecord->priority = $request->getBodyParam('priority');
        $sitemapMetadataRecord->changeFrequency = $request->getBodyParam('changeFrequency');
        $sitemapMetadataRecord->enabled = $request->getBodyParam('enabled');

        if (!SitemapsModule::getInstance()->sitemaps->saveSitemapMetadata($sitemapMetadataRecord)) {
            if (Craft::$app->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $sitemapMetadataRecord->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('sprout-module-sitemaps', "Couldn't save the Sitemap."));

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

    public function actionDeleteSitemapById(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $sitemapMetadataId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $result = SitemapsModule::getInstance()->sitemaps->deleteSitemapMetadataById($sitemapMetadataId);

        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $result >= 0,
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
