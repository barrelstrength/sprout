<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElementQuery;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElementQuery;
use Craft;

class SubscriberListsVariable
{
    public function lists(array $criteria = []): AudienceElementQuery
    {
        $query = AudienceElement::find();
        Craft::configure($query, $criteria);

        return $query;
    }

    public function subscribers(array $criteria = []): SubscriberElementQuery
    {
        $query = SubscriberElement::find();
        Craft::configure($query, $criteria);

        return $query;
    }
}
