<?php

namespace BarrelStrength\Sprout\redirects\components\elements\actions;

use BarrelStrength\Sprout\redirects\redirects\StatusCode;
use Craft;

class ChangePermanentStatusCode extends BaseStatusCodeAction
{
    public function getStatusCode(): int
    {
        return StatusCode::PERMANENT;
    }

    public function getTriggerLabel(): string
    {
        return Craft::t('sprout-module-redirects', 'Update Status Code to 301');
    }
}
