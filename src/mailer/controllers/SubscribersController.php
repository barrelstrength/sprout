<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberHelper;
use BarrelStrength\Sprout\mailer\subscriptions\Subscription;
use BarrelStrength\Sprout\mailer\subscriptions\SubscriptionRecord;
use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\Cp;
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
    /** @todo - add support for add/edit */
    public function actionEditUser(): \craft\web\Response
    {
        $user = new User();

        $options = MailerModule::getInstance()->subscriberLists->getListOptions();

        return $this->asCpScreen()
            ->title('Add Subscriber')
            ->editUrl($user->getCpEditUrl())
            ->addCrumb(Craft::t('sprout-module-mailer', 'Audience'), 'sprout/email/audience')
            ->action('sprout-module-mailer/subscribers/save-subscriber')
            ->redirectUrl('sprout/email/subscribers')
            ->contentTemplate('sprout-module-mailer/subscribers/_fields', [
                'user' => $user,
                'options' => $options,
                'values' => [],
            ]);
    }

    public function actionEditSubscriberTemplate($userId = null, $subscriber = null): Response
    {
        $this->requirePermission('sprout-module-mailer:editSubscribers');

        if ($userId !== null && $subscriber === null) {
            $subscription = new Subscription();
            $subscription->itemId = $userId;

            $subscriber = SubscriberHelper::getSubscriberOrItem($subscription);
        }

        if ($userId) {
            $subscriber = Craft::$app->getUsers()->getUserById($userId);
        }

        $lists = AudienceElement::find()->all();

        $options = [];

        foreach ($lists as $list) {
            $options[] = [
                'label' => $list->name,
                'value' => $list->getId(),
            ];
        }

        $subscriptionListIds = SubscriptionRecord::find()
            ->select('listId')
            ->where(['itemId' => $subscriber->id])
            ->column();

        return $this->renderTemplate('sprout-module-mailer/subscribers/_edit', [
            'user' => $subscriber,
            'options' => $options,
            'values' => $subscriptionListIds,
            'redirectUrl' => '',
            'continueEditingUrl' => '',
        ]);
    }

    public function actionCreateSubscriber(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $element = Craft::createObject(SubscriberElement::class);
        $element->siteId = $site->id;
        $element->enabled = true;

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save list as a draft: %s', implode(', ', $element->getErrorSummary(true))));
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'element' => $element,
        ]);

        return $this->redirect(UrlHelper::actionUrl());
    }

    public function actionSaveSubscriber(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('sprout-module-mailer:editSubscribers');

        // @todo - review, duplicating post assignments...
        $subscriber = $this->populateSubscriberFromPost();

        // Create inactive user, or get existing user account
        $email = Craft::$app->getRequest()->getBodyParam('email');
        $user = Craft::$app->getUsers()->ensureUserByEmail($email);

        // Add user data and save element
        $user->firstName = Craft::$app->getRequest()->getBodyParam('firstName');
        $user->lastName = Craft::$app->getRequest()->getBodyParam('lastName');

        Craft::$app->getElements()->saveElement($user, false);

        $subscriber = \craft\records\User::findOne($user->id);

        if (!$subscriber) {
            $record = new \craft\records\User();
            $record->id = $user->id;
            $record->save();
        }

        $listIds = Craft::$app->getRequest()->getBodyParam('listIds');

        foreach ($listIds as $listId) {
            $subscription = SubscriptionRecord::find()->where([
                'listId' => $listId,
                'itemId' => $user->id,
            ])->exists();

            if (!$subscription) {
                $record = new SubscriptionRecord();
                $record->listId = $listId;
                $record->itemId = $user->id;
                $record->save();
            }
            // Also delete any unselected subscriptions...
        }

        return $this->redirectToPostedUrl($user);

        //        if (!$listType->saveSubscriber($subscriber)) {
        //            Craft::$app->getSession()->setError(Craft::t('sprout-module-mailer', 'Unable to save subscriber.'));
        //
        //            Craft::$app->getUrlManager()->setRouteParams([
        //                'subscriber' => $subscriber,
        //            ]);
        //
        //            return null;
        //        }
        //
        //        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-mailer', 'Subscriber saved.'));
        //
        //        return $this->redirectToPostedUrl($subscriber);
    }

    public function saveSubscriber(SubscriberElement $subscriber): bool
    {
        if (Craft::$app->getElements()->saveElement($subscriber)) {
            $this->updateCount();

            return true;
        }

        return false;
    }

    public function actionDeleteSubscriber(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('sprout-module-mailer:editSubscribers');

        $subscriber = new SubscriberElement();
        $subscriber->id = Craft::$app->getRequest()->getBodyParam('subscriberId');

        if (!$this->deleteSubscriber($subscriber)) {
            Craft::$app->getSession()->setError(Craft::t('sprout-module-mailer', 'Unable to delete subscriber.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'subscriber' => $subscriber,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-mailer', 'Subscriber deleted.'));

        return $this->redirectToPostedUrl();
    }

    public function deleteSubscriber(SubscriberElement $subscriber): bool
    {
        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getElements()->deleteElementById($subscriber->id);

            // Clean up everything else that relates to this subscriber
            // @todo - review how to delete subscriber
            //            \craft\records\User::deleteAll('[[id]] = :id', [
            //                'id' => $subscriber->id,
            //            ]);
            SubscriptionRecord::deleteAll('[[listId]] = :listId', [
                ':listId' => $subscriber->id,
            ]);

            $this->updateCount();

            $transaction->commit();
        } catch (Exception) {

            $transaction->rollBack();

            throw new ElementNotFoundException(Craft::t('sprout-module-mailer', 'Unable to delete Subscriber.'));
        }

        return true;
    }

    public function populateSubscriberFromPost()
    {
        $subscriberId = Craft::$app->getRequest()->getBodyParam('subscriberId');

        $subscriber = Craft::$app->elements->getElementById($subscriberId);

        if (!$subscriber) {
            $subscriber = new SubscriberElement();
        }

        $subscriber->email = Craft::$app->getRequest()->getBodyParam('email');
        $subscriber->firstName = Craft::$app->getRequest()->getBodyParam('firstName');
        $subscriber->lastName = Craft::$app->getRequest()->getBodyParam('lastName');
        $subscriber->listIds = Craft::$app->getRequest()->getBodyParam('subscriberList.listIds');

        return $subscriber;
    }
}
