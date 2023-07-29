<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscriberlists\SubscriptionRecord;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class SubscriberListsController extends Controller
{
    /**
     * Adds a User to a Subscriber List
     */
    public function actionAdd(): ?Response
    {
        $this->requirePostRequest();

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
     * Removes a User from a Subscriber List
     */
    public function actionRemove(): ?Response
    {
        $this->requirePostRequest();

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

    protected function populateSubscriptionFromPost(): SubscriptionRecord
    {
        $subscription = new SubscriptionRecord([
            'subscriberListId' => Craft::$app->getRequest()->getRequiredBodyParam('audience.id'),
            'userId' => Craft::$app->getRequest()->getBodyParam('user.id'),
            'email' => Craft::$app->getRequest()->getBodyParam('user.email'),
        ]);

        return $subscription;
    }
}
