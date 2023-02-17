# Release Checklist

## Documentation

- Update documentation with relevant details
- Add Upgrading to `v.X.Y.Z` section to docs
- Summarize latest updates in Changelog
- Link to Upgrading section in docs if necessary

## Preflight

- Update Sprout Module package version in `composer.json`
- Update Plugin package version in `composer.json`
- Update Plugin required packages to new `barrelstrength/sprout` version
- Prepare respective plugins for release

## Release (Sprout Module and Plugins)

- Update Plugin schema version
- Update Sprout Module `barrelstrength\sprout::$schemaVersion`
- Generate schema audit file: `test/schema/X.Y.Z.json`
- Push latest releases on `develop` branch
- Merge latest releases from `develop` to the release branch (`v.X.Y.Z`)
- Tag latest release branches (`v.X.Y.Z`)
- Publish latest release branches
- Confirm expected releases have been published on Packagist and Craft Plugin Store

## Notify

- Notify customers with open tickets and feature requests about release
- Publish to #craftcms channels accordingly
