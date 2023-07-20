<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\core\relations\SourceElementRelationsEvent;
use craft\elements\Entry;

class NotificationEventHelper
{
    public static function getSourceElementRelations(SourceElementRelationsEvent $event): void
    {
        $event->sourceElementType = $event->data['sourceElementType'] ?? null;

        if (!$event->sourceElementType) {
            return;
        }

        $element = new $event->sourceElementType();

        // @todo - Add support for querying Craft Events
        if ($element instanceof Entry) {
            // Find Transactional Emails that use Events that act on this Element
        }

        $event->sourceElements = [];
    }
}
