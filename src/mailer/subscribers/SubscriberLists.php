<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscriptions\Subscription;
use BarrelStrength\Sprout\mailer\subscriptions\SubscriptionInterface;
use BarrelStrength\Sprout\mailer\subscriptions\SubscriptionRecord;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use Throwable;
use yii\db\Transaction;

class SubscriberLists extends Component
{
    public function getListOptions(): array
    {
        /** @var AudienceElement[] $lists */
        $lists = AudienceElement::find()->all();

        $options = [];

        foreach ($lists as $list) {
            $options[] = [
                'label' => $list->name,
                'value' => $list->getId(),
            ];
        }

        // Return a blank template if we have no lists
        if (empty($options)) {
            return [];
        }

        return $options;
    }

    public function add(SubscriptionInterface $subscription): bool
    {
        if ($this->requireEmailForSubscription === true) {
            $subscription->setScenario(Subscription::SCENARIO_SUBSCRIBER);
        }

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
                $item = new SubscriberElement();
                $item->userId = $subscription->itemId;
                $item->email = $subscription->email;
                $item->firstName = $subscription->firstName ?? null;
                $item->lastName = $subscription->lastName ?? null;
            }

            // Save or resave the subscriber. Make sure we have an ID and run User Sync.
            $this->saveSubscriber($item);

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

            if ($subscriptionRecord->save()) {
                $this->updateCount($subscriptionRecord->listId);
            } else {
                Craft::warning('List Item ' . $item->getId() . ' already exists on List ID ' . $list->id . '.', __METHOD__);
            }

            $transaction->commit();
        } catch (Throwable $throwable) {

            $transaction->rollBack();

            throw $throwable;
        }

        return true;
    }

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
            $this->updateCount();

            return true;
        }

        return false;
    }

    public function getList(SubscriptionInterface $subscription): AudienceElement|ElementInterface|null
    {
        $query = AudienceElement::find();

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

    /**
     * Get all Lists
     *
     * @return AudienceElement[]
     */
    public function getLists(): array
    {
        return AudienceElement::find()->all();
    }

    public function saveList(AudienceElement $list): bool
    {
        return Craft::$app->elements->saveElement($list);
    }

    /**
     * @return array|mixed
     */
    public function getSubscriptions(AudienceElement $list)
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

    /**
     */
    public function getCount(AudienceElement $list): int
    {
        $items = $this->getSubscriptions($list);

        return count($items);
    }

    /**
     * @todo - delegate this to the queue
     */
    public function updateCount($listId = null): bool
    {
        if ($listId === null) {
            $lists = AudienceElement::find()->all();
        } else {
            $list = AudienceElement::findOne($listId);

            $lists = [$list];
        }

        if ($lists === []) {
            return false;
        }

        /** @var AudienceElement[] $lists */
        foreach ($lists as $list) {

            if (!$list) {
                continue;
            }

            $count = MailerModule::getInstance()->subscriberLists->getCount($list);
            $list->count = $count;

            MailerModule::getInstance()->subscriberLists->saveList($list);
        }

        return true;
    }
}
