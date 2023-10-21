<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;

/**
 * @todo - Remove in Craft 5 in favor of native Element listing field
 */
interface NotificationEventRelationsTableInterface
{
    /**
     * Returns all supported classes or null to support everything
     */
    public function getAllowedNotificationEventRelationTypes(): array;

    /**
     * Returns an instance of the RelationsTableField to use in a FieldLayout
     */
    public function getNotificationEventRelationsTableField(): RelationsTableField;
}
