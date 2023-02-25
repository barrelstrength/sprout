<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElementQuery;
use Craft;
use craft\elements\db\UserQuery;
use craft\elements\User;

class SubscriberListsVariable
{
    public function lists(array $criteria = []): AudienceElementQuery
    {
        $query = AudienceElement::find();
        Craft::configure($query, $criteria);

        return $query;
    }

    // TODO: update SubscriberQueryBehavior and remove this method
    public function subscribers(array $criteria = []): UserQuery
    {
        $query = User::find();
        Craft::configure($query, $criteria);

        return $query;
    }
}
