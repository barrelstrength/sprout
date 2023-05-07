<?php

namespace BarrelStrength\Sprout\sitemaps\components;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataRecord;
use craft\elements\db\ElementQuery;

interface ElementSitemapMetadataInterface
{
    public static function getSourceDetails(): array;

    public function getElementQuery(ElementQuery $query, SitemapMetadataRecord $sitemapMetadata): ElementQuery;
}
