<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use yii\base\Event;

class RegisterElementSitemapMetadataEvent extends Event
{
    public array $metadataRules = [];
}
