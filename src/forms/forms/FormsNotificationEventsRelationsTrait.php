<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use Craft;

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

        $options = EmailTypeHelper::getEmailTypesOptions();

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-forms', 'New Email Type...'),
                'value' => '',
            ],
        ];

        foreach ($options as $option) {
            $optionValues[] = $option;
        }

        $createSelect = Cp::selectHtml([
            'id' => 'new-transactional-email',
            'name' => 'emailTypeUid',
            'options' => $optionValues,
            'value' => '',
        ]);

        $sidebarMessage = Craft::t('sprout-module-forms', 'This page lists any transactional email that are known to be related to the events triggered by this form.');
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage) , [
            'class' => 'meta read-only',
        ]);

        return new RelationsTableField([
            'attribute' => 'notification-event-relations',
            'rows' => $notificationEventRows,
            'newButtonHtml' => $createSelect,
            'sidebarHtml' => $sidebarHtml,
        ]);
    }
}
