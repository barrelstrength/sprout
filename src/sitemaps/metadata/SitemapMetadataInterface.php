<?php

namespace BarrelStrength\Sprout\sitemaps\metadata;

/**
 * Defines the interface that must be implemented
 * when adding SitemapMetadata behaviors to Elements.
 *
 * This facilitates looping through Elements
 * when generating XML Sitemaps
 */
interface SitemapMetadataInterface
{
    public function getSitemapMetadataTotalElements(): int;

    public function getSitemapMetadataElements(
        $elementGroupId,
        $offset,
        $limit,
        $site
    ): array;
}
