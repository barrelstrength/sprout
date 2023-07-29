<?php

namespace BarrelStrength\Sprout\mailer\subscriberlists;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElementQuery;
use Craft;

class SubscriberListsVariable
{
    public function subscriberLists(array $criteria = []): AudienceElementQuery
    {
        $query = AudienceElement::find();
        $query->type(SubscriberListAudienceType::class);
        Craft::configure($query, $criteria);

        return $query;
    }
}
