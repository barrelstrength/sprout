<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberHelper;
use BarrelStrength\Sprout\mailer\subscribers\Subscription;
use BarrelStrength\Sprout\mailer\subscribers\SubscriptionRecord;
use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use Exception;
use yii\db\Transaction;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class SubscribersController extends Controller
{
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
        $list->handle = Craft::$app->request->getBodyParam(
            'handle', StringHelper::toCamelCase($list->name)
        );

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
