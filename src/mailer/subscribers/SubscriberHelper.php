<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\subscriptions\Subscription;
use BarrelStrength\Sprout\mailer\subscriptions\SubscriptionInterface;

class SubscriberHelper
{
    /**
     * Get a Subscriber Element based on a subscription
     */
    public static function getSubscriberOrItem(SubscriptionInterface $subscription): SubscriberElement
    {
        /** @var Subscription $subscription */
        $subscriberId = $subscription->itemId;

        $query = SubscriberElement::find();

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

        /** @var SubscriberElement $subscriber */
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
