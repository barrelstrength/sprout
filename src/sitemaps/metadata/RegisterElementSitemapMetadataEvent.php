<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

use yii\base\Event;

class RegisterElementSitemapMetadataEvent extends Event
{
    public array $metadataRules = [];
}
