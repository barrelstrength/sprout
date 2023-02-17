<?php

namespace BarrelStrength\Sprout\forms\components\events;

use BarrelStrength\Sprout\forms\integrations\IntegrationLog;
use yii\base\Event;

class OnAfterIntegrationSubmitEvent extends Event
{
    public IntegrationLog $integrationLog;
}
