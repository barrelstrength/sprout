<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use craft\elements\db\ElementQuery;
use craft\models\Site;

interface
ElementSitemapMetadataInterface
{
    public static function getSourceDetails(Site $site): array;

    public function getElementQuery(ElementQuery $query, SitemapMetadataRecord $sitemapMetadata): ElementQuery;
}
