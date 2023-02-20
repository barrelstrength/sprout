# Changelog

## 4.0.0 - 2023-04-20

### Added

- Added support for Craft 4
- Added support for PHP 8
- Added `sprout` global variable to handle all APIs
- Added `_sprout` template root prefixed by the `privateTemplateTrigger` character
- Added `BarrelStrength\Sprout\core\db\MigrationHelper`
- Added `BarrelStrength\Sprout\core\db\MigrationTrait`
- Added `BarrelStrength\Sprout\core\db\SproutPluginMigrationInterface`
- Added `BarrelStrength\Sprout\core\db\SproutPluginMigrator`
- Added `BarrelStrength\Sprout\core\db\SproutTable`
- Added `BarrelStrength\Sprout\core\editions\Edition`
- Added `BarrelStrength\Sprout\core\editions\EditionTrait`
- Added `BarrelStrength\Sprout\core\modules\CpNavHelper`
- Added `BarrelStrength\Sprout\core\modules\Modules`
- Added `BarrelStrength\Sprout\core\modules\Settings`
- Added `BarrelStrength\Sprout\core\modules\SproutModuleTrait`
- Added `BarrelStrength\Sprout\core\modules\TranslatableTrait`
- Added `BarrelStrength\Sprout\core\sourcegroups\SourceGroupTrait`
- Added `barrelstrength/sprout` v4.0.0 (Sprout Framework)
- Added `nystudio107/craft-plugin-vite` v4.0.0
- Added namespace `BarrelStrength\Sprout`

### Changed

- Updated plugin settings to be managed via Sprout Framework modules
- Updated CHANGELOG to be broken out by module in `sprout/CHANGELOG`
- Moved Project Config settings from `sprout-base` => `sprout-module-[module]`
- Moved documented template variables to use `sprout.[module].[thing]`
- Moved undocumented template variables to use service layer via `sprout.modules.[module].[service]`
- Moved all controllers into Sprout Framework `sprout/[module]/[action]`
- Moved all front-end templates into to Sprout Framework `sprout/[templates]`
- Moved all services to use `Sprout::getInstance()->[service]`
- Added example project config settings in `sprout/examples/config/sprout-module-[module]`
- Moved all assets to into `sprout/assets`
- Moved `barrelstrength/sprout-base` => `barrelstrength/sprout`
- Moved translation category from `sprout-base-settings` => `sprout-module-core`
- Moved translation category from `sprout-base` => `sprout-module-[module]`
- Updated `craftcms/cms` to v4.0.0

### Removed

- Removed Plugin _Alternate Name_ setting
- Removed `barrelstrength/sprout-base` dependency
