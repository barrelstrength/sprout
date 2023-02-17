<?php

namespace BarrelStrength\Sprout\core\jobs;

use Craft;

class PurgeElementHelper
{
    /**
     * Queue a job to purge elements from the database
     */
    public static function purgeElements(PurgeElements $purgeElementsJob, $delay = null): void
    {
        if ($delay) {
            Craft::$app->getQueue()->delay($delay)->push($purgeElementsJob);
        } else {
            Craft::$app->getQueue()->push($purgeElementsJob);
        }
    }
}
