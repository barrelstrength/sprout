<?php

namespace BarrelStrength\Sprout\redirects\controllers;

use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\redirects\RedirectHelper;
use BarrelStrength\Sprout\redirects\redirects\StatusCode;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class RedirectsController extends Controller
{
    public function actionIndexTemplate(): Response
    {
        $this->requirePermission(RedirectsModule::p('editRedirects'));

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        return $this->renderTemplate('sprout-module-redirects/_redirects/index.twig', [
            'title' => RedirectElement::pluralDisplayName(),
            'elementType' => RedirectElement::class,
            'newRedirectButtonHtml' => RedirectHelper::getNewRedirectButtonHtml($site),
            'excludeUrlsButtonHtml' => RedirectHelper::getExcludeUrlsButtonHtml($site),
        ]);
    }

    public function actionSettingsTemplate(): Response
    {
        $this->requirePermission(RedirectsModule::p('editRedirects'));

        if (!RedirectsModule::isPro()) {
            throw new ForbiddenHttpException(RedirectsModule::getUpgradeMessage());
        }

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $settings = RedirectsModule::getInstance()->getSettings();

        return $this->renderTemplate('sprout-module-redirects/_redirects/settings.twig', [
            'settings' => $settings,
            'site' => $site,
        ]);
    }

    public function actionCreateRedirect(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $redirect = Craft::createObject(RedirectElement::class);
        $redirect->siteId = $site->id;
        $redirect->enabled = false;
        $redirect->structureId = RedirectHelper::getStructureId();
        $redirect->statusCode = StatusCode::TEMPORARY;

        // Save Element
        $redirect->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($redirect, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save report as a draft: %s', implode(', ', $redirect->getErrorSummary(true))));
        }

        // Redirect to edit page
        return $this->redirect($redirect->getCpEditUrl());
    }

    public function actionSaveDbSettings(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission(RedirectsModule::p('editRedirects'));

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $moduleId = RedirectsModule::getModuleId();
        $settings = Craft::$app->getRequest()->getBodyParam('settings');

        if (($settingsRecord = SettingsHelper::saveDbSettings($moduleId, $settings, $site->id)) === null) {
            Craft::$app->getSession()->setError(Craft::t('sprout-module-redirects', 'Couldn’t save settings.'));

            // Send the event back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settingsRecord,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-redirects', 'Redirect saved.'));

        return $this->redirectToPostedUrl($settingsRecord);
    }
}
