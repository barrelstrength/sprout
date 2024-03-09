<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\subscriberlists\SubscriptionRecord;
use Craft;
use craft\elements\User;
use Throwable;
use yii\base\Behavior;
use yii\db\Transaction;

class SproutSubscriberElementBehavior extends Behavior
{
    public ?array $sproutSubscriberListIds = null;

    public function events(): array
    {
        return array_merge(parent::events(), [
            User::EVENT_BEFORE_SAVE => 'beforeSave',
            User::EVENT_AFTER_PROPAGATE => 'afterPropagate',
        ]);
    }

    public function beforeSave(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        $newListIds = Craft::$app->getRequest()->getBodyParam('sproutSubscriberListIds', null);

        if ($newListIds === null) {
            return;
        }

        if (is_array($newListIds)) {
            $this->sproutSubscriberListIds = $newListIds;
        } else {
            $this->sproutSubscriberListIds = [];
        }
    }

    public function afterPropagate(): void
    {
        if ($this->sproutSubscriberListIds === null) {
            return;
        }

        /** @var Transaction $transaction */
        $transaction = Craft::$app->getDb()->beginTransaction();

        /** @var User|SproutSubscriberElementBehavior $user */
        $user = $this->owner;

        try {
            $oldListIds = array_keys($user->getSproutSubscriptions());

            // Remove all old subscriptions
            SubscriptionRecord::deleteAll([
                'subscriberListId' => $oldListIds,
                'userId' => $user->id,
            ]);

            foreach ($this->sproutSubscriberListIds as $listId) {
                $subscriptionRecord = new SubscriptionRecord();
                $subscriptionRecord->subscriberListId = $listId;
                $subscriptionRecord->userId = $user->id;

                $subscriptionRecord->save();
            }

            $transaction->commit();
        } catch (Throwable $throwable) {

            $transaction->rollBack();

            throw $throwable;
        }
    }

    public function getSproutSubscriberLists(): array
    {
        $listIds = SubscriptionRecord::find()
            ->select(['subscriberListId'])
            ->where(['userId' => $this->owner->id])
            ->column();

        return AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->id($listIds)
            ->all();
    }

    public function getSproutSubscriptions(): array
    {
        return SubscriptionRecord::find()
            ->select(['*'])
            ->where(['userId' => $this->owner->id])
            ->indexBy('subscriberListId')
            ->all();
    }
}
