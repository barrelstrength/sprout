<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\records\User;
use Throwable;
use yii\db\Transaction;

class SubscriberLists extends Component
{
    // TODO - refactor for new Subscriber Users
    public function add(SubscriptionInterface $subscription): bool
    {
        if (!$subscription->validate()) {
            return false;
        }

        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            /** @var Element $item */
            $item = SubscriberHelper::getSubscriberOrItem($subscription);

            // If our Subscriber doesn't exist, create a Subscriber Element
            if ($item === null) {
                $item = new User();
                $item->id = $subscription->itemId;
                $item->email = $subscription->email;
                $item->firstName = $subscription->firstName ?? null;
                $item->lastName = $subscription->lastName ?? null;
            }

            // Save or resave the subscriber. Make sure we have an ID and run User Sync.
            //$this->saveSubscriber($item);

            $subscription->itemId = $item->getId();

            $list = $this->getList($subscription);

            // If our List doesn't exist, create a List Element
            if ($list === null) {
                $subscription->addErrors([
                    'listId' => [
                        Craft::t('sprout-module-mailer', 'List does not exist.'),
                        Craft::t('sprout-module-mailer', 'User not permitted to create List.'),
                    ],
                ]);

                return false;
            }

            if (!$item->validate() || !$list->validate()) {
                $subscription->addErrors($item->getErrors());
                $subscription->addErrors($list->getErrors());

                return false;
            }

            $subscriptionRecord = new SubscriptionRecord();
            $subscriptionRecord->listId = $list->id;
            $subscriptionRecord->itemId = $item->getId();

            if (!$subscriptionRecord->save()) {
                Craft::warning('List Item ' . $item->getId() . ' already exists on List ID ' . $list->id . '.', __METHOD__);
            }

            $transaction->commit();
        } catch (Throwable $throwable) {

            $transaction->rollBack();

            throw $throwable;
        }

        return true;
    }

    // TODO - refactor for new Subscriber Users
    public function remove(SubscriptionInterface $subscription): bool
    {
        $list = $this->getList($subscription);

        if (!$list) {
            return false;
        }

        $item = SubscriberHelper::getSubscriberOrItem($subscription);

        if (!$item) {
            return false;
        }

        // Delete the subscription that matches the List and Subscriber IDs
        $subscriptions = SubscriptionRecord::deleteAll([
            '[[listId]]' => $list->id,
            '[[itemId]]' => $item->id,
        ]);

        if ($subscriptions !== null) {
            return true;
        }

        return false;
    }

    public function getList(SubscriptionInterface $subscription): AudienceElement|ElementInterface|null
    {
        $query = AudienceElement::find()
            ->type(SubscriberListAudienceType::class);

        if ($subscription->listId) {
            $query->andWhere([
                'sprout_audiences.id' => $subscription->listId,
            ]);

            return $query->one();
        }

        if ($subscription->elementId && $subscription->listHandle) {
            $query->andWhere([
                'and',
                ['sprout_audiences.elementId' => $subscription->elementId],
                ['sprout_audiences.handle' => $subscription->listHandle],
            ]);
        } else {
            // Give the user what we can, but this result may not be unique in all cases
            $query->andWhere([
                'or',
                ['sprout_audiences.elementId' => $subscription->elementId],
                ['sprout_audiences.handle' => $subscription->listHandle],
            ]);
        }

        return $query->one();
    }

    public function getSubscriptions(AudienceElement $list): array
    {
        return SubscriptionRecord::find()
            ->where(['listId' => $list->id])
            ->all();
    }

    public function populateSubscriptionFromCriteria(array $criteria = []): Subscription
    {
        $subscription = new Subscription();
        $subscription->listId = $criteria['listId'] ?? null;
        $subscription->listHandle = $criteria['listHandle'] ?? null;
        $subscription->itemId = $criteria['itemId'] ?? null;
        $subscription->email = $criteria['email'] ?? null;

        return $subscription;
    }

    public function isSubscribed(SubscriptionInterface $subscription): bool
    {
        $list = $this->getList($subscription);

        // If we don't find a matching list, no subscription exists
        if ($list === null) {
            return false;
        }

        // Make sure we set all the values we can
        if (!empty($subscription->listId)) {
            $subscription->listId = $list->id;
        }

        if (!empty($subscription->listHandle)) {
            $subscription->listHandle = $list->handle;
        }

        $item = SubscriberHelper::getSubscriberOrItem($subscription);

        if ($item === null) {
            return false;
        }

        return SubscriptionRecord::find()->where([
            'listId' => $list->id,
            'itemId' => $item->id,
        ])->exists();
    }
}
