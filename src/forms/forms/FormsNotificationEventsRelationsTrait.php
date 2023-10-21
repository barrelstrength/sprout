<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use craft\events\RegisterComponentTypesEvent;

trait FormsNotificationEventsRelationsTrait
{
    public function getAllowedNotificationEventRelationTypes(): array
    {
        $dataSourceTypes = [
            SaveSubmissionNotificationEvent::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $dataSourceTypes,
        ]);

        /** @var FormElement $this */
        $this->trigger(FormElement::EVENT_REGISTER_DATA_SOURCE_RELATIONS_TYPES, $event);

        return $event->types;
    }

    public function getNotificationEventRelationsTableField(): RelationsTableField
    {
        $notificationEventRows = TransactionalModule::getInstance()->notificationEvents->getTransactionalRelations($this);

        return new RelationsTableField([
            'attribute' => 'notification-event-relations',
            'rows' => $notificationEventRows,
        ]);
    }
}
