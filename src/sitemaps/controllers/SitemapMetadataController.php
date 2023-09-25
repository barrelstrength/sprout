<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ContentSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\CustomPagesSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\CustomQuerySitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\ElementUriHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
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
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SitemapMetadataController extends Controller
{
    public function actionSitemapMetadataIndexTemplate(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));

        $editableSiteIds = SitemapsMetadataHelper::getEditableSiteIds();
        $firstSiteInGroup = SitemapsMetadataHelper::getFirstSiteInGroup($site);

        $contentSitemapMetadata = ContentSitemapMetadataHelper::getContentSitemapMetadata($site);
        $contentQueries = CustomQuerySitemapMetadataHelper::getContentQuerySitemapMetadata($site);
        $customPages = CustomPagesSitemapMetadataHelper::getCustomPagesSitemapMetadata($site);

        $allowedElementTypes = array_unique(array_column($contentSitemapMetadata, 'type'));
        $elementsWithUris = ElementUriHelper::getElementsWithUrisForSitemaps($allowedElementTypes);

        return $this->renderTemplate('sprout-module-sitemaps/_sitemapmetadata/index.twig', [
            'title' => Craft::t('sprout-module-sitemaps', 'Sitemaps'),
            'site' => $site,
            'firstSiteInGroup' => $firstSiteInGroup,
            'editableSiteIds' => $editableSiteIds,
            'elementsWithUris' => $elementsWithUris,
            'contentSitemapMetadata' => $contentSitemapMetadata,
            'contentQueries' => $contentQueries,
            'customPages' => $customPages,
            'settings' => SitemapsModule::getInstance()->getSettings(),
            'displayViewSitemapXmlButton' => $site->id === $firstSiteInGroup->id,
            'DEFAULT_PRIORITY' => SitemapsSettings::DEFAULT_PRIORITY,
            'DEFAULT_CHANGE_FREQUENCY' => SitemapsSettings::DEFAULT_CHANGE_FREQUENCY,
        ]);
    }

    public function actionCustomSitemapMetadataEditTemplate(string $sourceKey, string $sitemapMetadataUid = null, SitemapMetadataRecord $sitemapMetadata = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(SitemapsModule::p('editSitemaps'));
        $this->requirePermission('editSite:' . $site->uid);

        if (!$sitemapMetadata) {
            if ($sitemapMetadataUid) {
                $sitemapMetadata = SitemapsMetadataHelper::getSitemapMetadataByUid($sitemapMetadataUid, $site);
            } else {
                $sitemapMetadata = new SitemapMetadataRecord();
                $sitemapMetadata->siteId = $site->id;
            }
        }

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

                // Don't allow any site conditions in the builder, we manage these in Sitemaps module
                $condition->queryParams[] = 'site';

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

        $continueEditingUrl = UrlHelper::cpUrl('sprout/sitemaps/edit/' . $sourceKey . '/{uid}');

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

        if (!$sitemapMetadataRecord = SitemapMetadataRecord::findOne($sitemapMetadataId)) {
            $sitemapMetadataRecord = new SitemapMetadataRecord();
        }

        $request = Craft::$app->getRequest();

        $siteId = $request->getBodyParam('siteId');
        $enabled = (bool)$request->getBodyParam('enabled');
        $sourceKey = $request->getBodyParam('sourceKey');

        // Lite/Pro check - only allow 5 Content or Content Query Sitemaps per site
        if ($enabled && // Only validate if a new sitemap is being enabled
            !$sitemapMetadataRecord->enabled && // Allow updates to existing enabled sitemaps
            $sourceKey !== SitemapKey::CUSTOM_PAGES && // Custom Pages Sitemap is not limited
            ContentSitemapMetadataHelper::hasReachedSitemapLimit($siteId) // Enforce limit of 5 Content or Content Query Sitemaps
        ) {
            if (Craft::$app->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errorMessage' => Craft::t('sprout-module-sitemaps', SitemapsModule::getUpgradeMessage()),
                ]);
            }

            Craft::$app->getSession()->setError(
                Craft::t('sprout-module-sitemaps', SitemapsModule::getUpgradeMessage())
            );

            Craft::$app->getUrlManager()->setRouteParams([
                'sitemapMetadata' => $sitemapMetadataRecord,
            ]);

            return null;
        }

        $type = $request->getBodyParam('type');

        $sitemapMetadataRecord->siteId = $siteId;
        $sitemapMetadataRecord->sourceKey = $sourceKey;
        $sitemapMetadataRecord->type = $type;
        $sitemapMetadataRecord->uri = $request->getBodyParam('uri', $sitemapMetadataRecord->uri);
        $sitemapMetadataRecord->priority = $request->getBodyParam('priority', $sitemapMetadataRecord->priority);
        $sitemapMetadataRecord->changeFrequency = $request->getBodyParam('changeFrequency', $sitemapMetadataRecord->changeFrequency);
        $sitemapMetadataRecord->description = $request->getBodyParam('description', $sitemapMetadataRecord->description);
        $sitemapMetadataRecord->enabled = $enabled;

        if ($sourceKey === SitemapKey::CUSTOM_PAGES) {
            $sitemapMetadataRecord->type = null;
            $sitemapMetadataRecord->uri = ltrim($sitemapMetadataRecord->uri, '/');
        }

        // No need to save condition from Sitemap Index, only from edit page
        if ($sourceKey === SitemapKey::CUSTOM_QUERY && !$this->request->getAcceptsJson()) {
            $conditionBuilderParam = $type::lowerDisplayName() . '-conditionRules';
            $condition = $request->getBodyParam($conditionBuilderParam);
            $sitemapMetadataRecord->settings = $condition;
        }

        if (!SitemapsModule::getInstance()->sitemaps->saveSitemapMetadata($sitemapMetadataRecord)) {
            if (Craft::$app->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
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
                'success' => $result,
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
