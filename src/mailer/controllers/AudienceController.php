<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\mailer\audience\AudienceTypeInterface;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\Element;
use craft\errors\MissingComponentException;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use http\Exception\InvalidArgumentException;
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
        if (!MailerModule::getInstance()->getSettings()->enableAudiences) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $this->requirePermission(MailerModule::p('editAudiences'));

        $audienceTypes = MailerModule::getInstance()->audiences->getAudienceTypes();

        if (!$audienceTypes) {
            throw new MissingComponentException('No Audience Types are enabled. Enable the Subscriber Audience Type in the settings to get started.');
        }

        return $this->renderTemplate('sprout-module-mailer/audience/index.twig', [
            'title' => AudienceElement::pluralDisplayName(),
            'elementType' => AudienceElement::class,
            'audienceTypes' => ComponentHelper::typesToInstances($audienceTypes),
        ]);
    }

    public function actionCreateAudience(string $type = null): Response
    {
        $this->requirePermission(MailerModule::p('editAudiences'));

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        if (!$type) {
            throw new InvalidArgumentException('No Audience Type provided.');
        }

        $audience = new $type();

        if (!$audience instanceof AudienceTypeInterface) {
            throw new MissingComponentException('Unable to create audience of type: ' . $type);
        }

        $element = Craft::createObject(AudienceElement::class);
        $element->name = '';
        $element->handle = '';
        $element->siteId = $site->id;
        $element->enabled = true;
        $element->type = $audience::class;

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save list as a draft: %s', implode(', ', $element->getErrorSummary(true))));
        }

        return $this->redirect($element->getCpEditUrl());
    }
}
