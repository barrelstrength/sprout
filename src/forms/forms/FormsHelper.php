<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\datastudio\datasources\DataSources;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvents;
use craft\events\RegisterComponentTypesEvent;

class FormsHelper
{
    public static function registerNotificationEventRelationsTypes(RegisterComponentTypesEvent $event): void
    {
        if (!$event->sender instanceof NotificationEvents) {
            return;
        }

        $event->types[] = SaveSubmissionNotificationEvent::class;
    }

    public static function registerDataSourceRelationsTypes(RegisterComponentTypesEvent $event): void
    {
        if (!$event->sender instanceof DataSources) {
            return;
        }

        $event->types[] = SubmissionsDataSource::class;
    }
}
