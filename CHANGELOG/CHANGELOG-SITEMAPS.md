# Changelog

## 4.1.4 - UNRELEASED

### Fixed

- Fixed dynamic reference to Content Query condition class names when building non-Entry queries
- Fixed an issue where a disabled sitemap URL accessed directly would render the Sitemap Index

## 4.1.3 - 2023-09-25

### Added

- Added support for Craft 4
- Added Custom Query Sitemap Builder for Entries, Categories, and Products
- Added _View sitemap.xml_ button to Sitemap Index page
- Added Sitemap Metadata API

### Changed

- Updated generated sitemap URLs to use UUIDs
- Renamed setting `enableCustomSections` => `enableCustomPagesSitemap`
- Renamed setting `enableMultilingualSitemaps` => `sitemapAggregationMethod`
- URL Enabled Sections API has been removed in favor of new Sitemap Metadata API
- Custom integrations have been updated from `barrelstrength\sproutbaseuris\services\UrlEnabledSections::EVENT_REGISTER_URL_ENABLED_SECTION_TYPES` => `BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadata::EVENT_REGISTER_ELEMENT_SITEMAP_METADATA`
- Updated Project Config settings from `sprout-base-sitemaps` => `sprout-module-sitemaps`
- Updated translation category from `sprout-base-sitemaps` => `sprout-module-sitemaps`
- Migrated `barrelstrength/sprout-base-sitemaps` => `barrelstrength/sprout`

### Removed

- Removed `enableDynamicSitemaps` setting in favor of Sitemap module setting
- Removed `barrelstrength/sprout-base-sitemaps` dependency



