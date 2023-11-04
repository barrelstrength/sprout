<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\events\ModifyRelationsTableQueryEvent;
use BarrelStrength\Sprout\datastudio\datasources\DataSources;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvents;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\db\Expression;

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

