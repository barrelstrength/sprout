<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\Element;
use craft\errors\MissingComponentException;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class AudienceController extends Controller
{
    /**
     * Allow users who are not logged in to subscribe and unsubscribe from lists
     */
    protected int|bool|array $allowAnonymous = [
        'add',
        'remove',
    ];

    public function actionAudienceIndexTemplate(): Response
    {
        $this->requirePermission(MailerModule::p('editAudiences'));

        $audienceTypes = MailerModule::getInstance()->audiences->getAudienceTypeInstances();

        if (!$audienceTypes) {
            throw new MissingComponentException('No Audience Types are enabled. Enable the Subscriber Audience Type in the settings to get started.');
        }

        return $this->renderTemplate('sprout-module-mailer/audience/index', [
            'title' => AudienceElement::pluralDisplayName(),
            'elementType' => AudienceElement::class,
            'audienceTypes' => $audienceTypes,
        ]);
    }

    public function actionCreateAudience(string $audienceTypeHandle = null): Response
    {
        $this->requirePermission(MailerModule::p('editAudiences'));

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $element = Craft::createObject(AudienceElement::class);
        $element->siteId = $site->id;
        $element->enabled = true;

        $audiences = MailerModule::getInstance()->audiences->getAudienceTypeInstances();

        foreach ($audiences as $audience) {
            if ($audience->getHandle() === $audienceTypeHandle) {
                $element->audienceType = $audience::class;
                break;
            }
        }

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save list as a draft: %s', implode(', ', $element->getErrorSummary(true))));
        }

        return $this->redirect($element->getCpEditUrl());
    }
}
