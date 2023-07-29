<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\fieldlayoutelements\SubscriberListsField;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElementBehavior;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberQueryBehavior;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\ModelEvent;
use craft\events\RegisterElementSourcesEvent;
use Throwable;
use yii\db\Transaction;

class SubscriberHelper
{
    public static function attachSubscriberElementBehavior(DefineBehaviorsEvent $event): void
    {
        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->behaviors[SubscriberElementBehavior::class] = SubscriberElementBehavior::class;
        }
    }

    public static function attachSubscriberQueryBehavior(DefineBehaviorsEvent $event): void
    {
        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->behaviors[SubscriberQueryBehavior::class] = SubscriberQueryBehavior::class;
        }
    }

    public static function defineNativeSubscriberField(DefineFieldLayoutFieldsEvent $event): void
    {
        if ($event->sender->type !== User::class) {
            return;
        }

        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->fields[] = SubscriberListsField::class;
        }
    }

    public static function defineAdditionalSources(RegisterElementSourcesEvent $event): void
    {
        if ($event->context !== 'index') {
            return;
        }

        $settings = MailerModule::getInstance()->getSettings();

        if (!$settings->enableSubscriberLists) {
            return;
        }

        /** @var AudienceElement[] $lists */
        $lists = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $sources = [];

        if (!empty($lists)) {
            $sources[] = [
                'heading' => Craft::t('sprout-module-mailer', 'Subscriber Lists'),
            ];

            foreach ($lists as $list) {
                $source = [
                    'key' => 'subscriber-lists:' . $list->getId(),
                    'label' => $list->name,
                    'data' => [
                        'handle' => $list->handle,
                    ],
                    'criteria' => [
                        'subscriberListId' => $list->getId(),
                    ],
                ];

                $sources[] = $source;
            }
        }

        $event->sources = array_merge($event->sources, $sources);
    }

    public static function handleSaveAllSubscriberListsForUser(ModelEvent $event): void
    {
        $user = $event->sender;

        $newListIds = Craft::$app->getRequest()->getBodyParam('sprout.subscriberListIds');

        self::saveAllSubscriberListsForUser($user, $newListIds);
    }

    public static function saveAllSubscriberListsForUser(User $user, array $newListIds = []): void
    {
        if (!$newListIds) {
            return;
        }

        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            /** @var User|SubscriberElementBehavior $user */
            $oldListIds = $user->getSubscriberListsIds();

            // Remove all old subscriptions
            SubscriptionRecord::deleteAll([
                'itemId' => $user->id,
                'listId' => $oldListIds,
            ]);

            foreach ($newListIds as $listId) {
                $subscriptionRecord = new SubscriptionRecord();
                $subscriptionRecord->listId = $listId;
                $subscriptionRecord->itemId = $user->id;

                $subscriptionRecord->save();
            }

            $transaction->commit();
        } catch (Throwable $throwable) {

            $transaction->rollBack();

            throw $throwable;
        }
    }

    public static function getSubscriberListOptions(): array
    {
        /** @var AudienceElement[] $lists */
        $lists = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $options = [];

        array_map(static function($list) use (&$options) {
            $options[] = [
                'label' => $list->name,
                'value' => $list->getId(),
            ];
        }, $lists);

        return $options;
    }

    /**
     * Get a Subscriber Element based on a subscription
     */
    public static function getSubscriberOrItem(SubscriptionInterface $subscription): User
    {
        /** @var Subscription $subscription */
        $subscriberId = $subscription->itemId;

        $query = User::find();

        if ($subscription->email) {
            $query->andWhere([
                'users.email' => $subscription->email,
            ]);
        } else {
            $query->andWhere([
                'users.id' => $subscriberId,
            ]);
            $query->orWhere([
                'users.id' => $subscriberId,
            ]);
        }

        /** @var User $subscriber */
        $subscriber = $query->one();

        // Only assign profile values when we add a Subscriber if we have values
        // Don't overwrite any profile attributes with empty values
        if (!empty($subscription->firstName)) {
            $subscriber->firstName = $subscription->firstName;
        }

        if (!empty($subscription->lastName)) {
            $subscriber->lastName = $subscription->lastName;
        }

        return $subscriber;
    }
}
