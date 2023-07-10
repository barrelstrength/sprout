<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\core\relations\SourceElementRelationsEvent;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElementBehavior;
use craft\base\Component;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;

class NotificationEventHelper
{
    public static function attachBehaviors(DefineBehaviorsEvent $event): void
    {
        $event->behaviors[TransactionalEmailElementBehavior::class] = TransactionalEmailElementBehavior::class;
    }

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
