<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\sitemaps\sitemapsections\SitemapSectionRecord;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\uris\components\sectiontypes\NoSectionSectionType;
use Craft;
use craft\web\Controller;
use yii\db\ActiveRecord;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SitemapsController extends Controller
{
    /**
     * Renders the Sitemap Index Page
     */
    public function actionSitemapIndexTemplate(): Response
    {
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $siteHandle = Craft::$app->getRequest()->getQueryParam('site');

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
            if ($siteHandle !== null) {

                // If we have a handle, the Current Site and First Site in Group may be different
                $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

                if (!$currentSite) {
                    throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
                }

                $currentSiteGroup = Craft::$app->sites->getGroupById($currentSite->groupId);

                if (!$currentSiteGroup) {
                    throw new NotFoundHttpException('Site group not found.');
                }

                $sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);
                $firstSiteInGroup = $sitesInCurrentSiteGroup[0];
            } else {
                // If we don't have a handle, we'll load the first site in the first group
                // We assume at least one site group and the Current Site will be the same as the First Site

                $allSiteGroups = Craft::$app->sites->getAllGroups();
                $currentSiteGroup = $allSiteGroups[0];
                $sitesInCurrentSiteGroup = Craft::$app->sites->getSitesByGroupId($currentSiteGroup->id);
                $firstSiteInGroup = $sitesInCurrentSiteGroup[0];
                $currentSite = $firstSiteInGroup;
            }
        } else {
            // For a single site, the primary site ID will do
            $currentSite = Craft::$app->getSites()->getPrimarySite();
            $firstSiteInGroup = $currentSite;
        }

        $urlEnabledSectionTypes = SitemapsModule::getInstance()->sitemaps->getUrlEnabledSectionTypesForSitemaps($currentSite->id);

        $customSections = SitemapsModule::getInstance()->sitemaps->getCustomSitemapSections($currentSite->id);

        return $this->renderTemplate('sprout-module-sitemaps/sitemaps/index', [
            'currentSite' => $currentSite,
            'firstSiteInGroup' => $firstSiteInGroup,
            'editableSiteIds' => $editableSiteIds,
            'urlEnabledSectionTypes' => $urlEnabledSectionTypes,
            'customSections' => $customSections,
            'settings' => $settings,
        ]);
    }

    /**
     * Renders a Sitemap Edit Page
     */
    public function actionSitemapEditTemplate(int $sitemapSectionId = null, SitemapSectionRecord $sitemapSectionRecord = null): Response
    {
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $siteHandle = Craft::$app->getRequest()->getRequiredQueryParam('site');

        if ($siteHandle === null) {
            throw new NotFoundHttpException('Unable to find site with handle: ' . $siteHandle);
        }

        $currentSite = Craft::$app->getSites()->getSiteByHandle($siteHandle);

        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // Make sure the user has permission to edit that site
        if ($currentSite !== null && !in_array($currentSite->id, $editableSiteIds, false)) {
            throw new ForbiddenHttpException('User not permitted to edit content for this site.');
        }

        if (!$sitemapSectionRecord instanceof ActiveRecord) {
            if ($sitemapSectionId) {
                $sitemapSectionRecord = SitemapsModule::getInstance()->sitemaps->getSitemapSectionById($sitemapSectionId);
            } else {
                $sitemapSectionRecord = new SitemapSectionRecord();
                $sitemapSectionRecord->siteId = $currentSite->id;
                $sitemapSectionRecord->type = NoSectionSectionType::class;
            }
        }

        $continueEditingUrl = 'sprout/sitemaps/edit/{id}?site=' . $currentSite->handle;

        $tabs = [
            [
                'label' => 'Custom Page',
                'url' => '#tab1',
                'class' => null,
            ],
        ];

        return $this->renderTemplate('sprout-module-sitemaps/sitemaps/_edit', [
            'currentSite' => $currentSite,
            'sitemapSection' => $sitemapSectionRecord,
            'continueEditingUrl' => $continueEditingUrl,
            'tabs' => $tabs,
        ]);
    }

    public function actionSaveSitemapSection(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $id = Craft::$app->getRequest()->getBodyParam('id');

        $sitemapSectionRecord = SitemapSectionRecord::findOne($id);

        if ($sitemapSectionRecord === null) {
            $sitemapSectionRecord = new SitemapSectionRecord();
        }

        $request = Craft::$app->getRequest();

        $sitemapSectionRecord->siteId = $request->getBodyParam('siteId');
        $sitemapSectionRecord->urlEnabledSectionId = $request->getBodyParam('urlEnabledSectionId');
        $sitemapSectionRecord->uri = $request->getBodyParam('uri');
        $sitemapSectionRecord->type = $request->getBodyParam('type');
        $sitemapSectionRecord->priority = $request->getBodyParam('priority');
        $sitemapSectionRecord->changeFrequency = $request->getBodyParam('changeFrequency');
        $sitemapSectionRecord->enabled = $request->getBodyParam('enabled');

        if (!SitemapsModule::getInstance()->sitemaps->saveSitemapSection($sitemapSectionRecord)) {
            if (Craft::$app->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $sitemapSectionRecord->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('sprout-module-sitemaps', "Couldn't save the Sitemap."));

            Craft::$app->getUrlManager()->setRouteParams([
                'sitemapSection' => $sitemapSectionRecord,
            ]);

            return null;
        }

        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'sitemapSection' => $sitemapSectionRecord,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-sitemaps', 'Sitemap saved.'));

        return $this->redirectToPostedUrl($sitemapSectionRecord);
    }

    /**
     * Deletes a Sitemap Section
     */
    public function actionDeleteSitemapById(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $sitemapSectionId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $result = SitemapsModule::getInstance()->sitemaps->deleteSitemapSectionById($sitemapSectionId);

        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => $result >= 0,
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
