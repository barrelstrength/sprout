<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

class SourceKeyHelper
{
    public static function findElementGroupId($sourceKey): ?int
    {
        $parts = explode('-', $sourceKey);

        $elementGroupId = (int)$parts[1];

        return $elementGroupId > 0 ? $elementGroupId : null;
    }
}
