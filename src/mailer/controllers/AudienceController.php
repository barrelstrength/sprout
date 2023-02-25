<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\audience\AudienceGroupRecord;
use BarrelStrength\Sprout\mailer\audience\AudienceGroupsAdmin;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscribers\Subscription;
use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
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

    public function actionAudienceIndexTemplate($groupId = null): Response
    {
        $this->requirePermission('sprout-module-mailer:editLists');

        return $this->renderTemplate('sprout-module-mailer/audience/index', [
            'title' => AudienceElement::pluralDisplayName(),
            'elementType' => AudienceElement::class,
            'groupId' => $groupId,
        ]);
    }

    public function actionCreateAudience(string $audienceTypeHandle = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $listElement = Craft::createObject(AudienceElement::class);
        $listElement->siteId = $site->id;
        $listElement->enabled = true;

        $audiences = MailerModule::getInstance()->audiences->getAudiences();

        foreach ($audiences as $audience) {
            if ($audience->getHandle() === $audienceTypeHandle) {
                $listElement->audienceType = $audience::class;
                break;
            }
        }

        // Save it
        $listElement->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($listElement, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save list as a draft: %s', implode(', ', $listElement->getErrorSummary(true))));
        }

        return $this->redirect($listElement->getCpEditUrl());
    }

    /**
     * Adds a subscriber to a list
     */
    public function actionAdd(): ?Response
    {
        $this->requirePostRequest();

        /** @var Subscription $subscription */
        $subscription = $this->populateSubscriptionFromPost();

        if (!MailerModule::getInstance()->subscriberLists->add($subscription)) {

            if (Craft::$app->getRequest()->getIsAjax()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $subscription->getErrors(),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                'subscription' => $subscription,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getIsAjax()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Removes a subscriber from a list
     */
    public function actionRemove(): ?Response
    {
        $this->requirePostRequest();

        /** @var Subscription $subscription */
        $subscription = $this->populateSubscriptionFromPost();

        if (!MailerModule::getInstance()->subscriberLists->remove($subscription)) {
            if (Craft::$app->getRequest()->getIsAjax()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $subscription->getErrors(),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                'subscription' => $subscription,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getIsAjax()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl();
    }

    protected function populateListFromPost()
    {
        $listId = Craft::$app->getRequest()->getBodyParam('listId');

        $list = Craft::$app->elements->getElementById($listId);

        if (!$list) {
            $list = new AudienceElement();
        }

        $list->elementId = Craft::$app->getRequest()->getBodyParam('elementId');
        $list->name = Craft::$app->request->getRequiredBodyParam('name');
        $list->handle = Craft::$app->request->getBodyParam('handle');

        if ($list->handle === null) {
            $list->handle = StringHelper::toCamelCase($list->name);
        }

        return $list;
    }

    protected function populateSubscriptionFromPost(): Subscription
    {
        $subscription = new Subscription();
        $subscription->listId = Craft::$app->getRequest()->getBodyParam('list.id');
        $subscription->elementId = Craft::$app->getRequest()->getBodyParam('list.elementId');
        $subscription->listHandle = Craft::$app->getRequest()->getBodyParam('list.handle');
        $subscription->email = Craft::$app->getRequest()->getBodyParam('subscription.email');
        $subscription->firstName = Craft::$app->getRequest()->getBodyParam('subscription.firstName');
        $subscription->lastName = Craft::$app->getRequest()->getBodyParam('subscription.lastName');

        return $subscription;
    }
}
