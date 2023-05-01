<?php

namespace BarrelStrength\Sprout\uris\elementgroups;

use BarrelStrength\Sprout\sitemaps\metadata\SitemapMetadataRecord;
use craft\base\Model;

class ElementGroup extends Model
{
    public int $id;

    public SitemapMetadataRecord $sitemapMetadata;
}
