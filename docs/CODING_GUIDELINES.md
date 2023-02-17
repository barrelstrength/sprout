# Coding Guidelines

A central principle of the Sprout Plugin Suite is to create an experience – for
both users and developers – that looks and feels like the native experience with
Craft CMS. Toward that end, wherever possible, Sprout adopts conventions set
forth by Craft CMS. General conventions include:

| Convention | Notes |
|:---------- |:----- |
| [Craft CMS codebase](https://github.com/craftcms/cms) | Use Craft's APIs in the same way that Craft uses them |
| [Coding Guidelines](https://docs.craftcms.com/v3/extend/coding-guidelines.html) | Follow conventions outlined in Craft's Coding Guidelines | 
| [PhpStorm Settings](https://github.com/barrelstrength/phpstorm-settings) | Use the 'Barrel Strength - Craft CMS Project' Code Styles and Inspections. These were initially inspired by the infrequently-updated [Craft Code Style](https://github.com/craftcms/phpstorm-settings) and [Craft Inspections](https://github.com/craftcms/phpstorm-settings) with some additional customization. |
| [Php Inspections (EA Extended)](https://plugins.jetbrains.com/plugin/7622-php-inspections-ea-extended-) | More code conventions for PhpStorm |
| [Yii2 and Craft CMS 3 Inspections](https://plugins.jetbrains.com/plugin/9400-yii2-inspections) | Yii 2 and Craft-specific inspections in PhpStorm |
| [Sass Mixins for Craft CMS](https://github.com/craftcms/sass) | Use Craft's Control Panel style conventions (reference) |
| [Craft UI](https://www.npmjs.com/package/@pixelandtonic/craftui) | Vue.js components and styles for Craft CMS apps (reference) | 

Where there are no clear Craft conventions for our codebase or workflows we
endeavor to establish our own conventions that align with the Craft User
Experience and are consistent with cultural conventions in the Craft community.
An incomplete list of these conventions are outlined below.

## Git Workflow

We use a git workflow in the spirit of
the [Git branching model](http://nvie.com/posts/a-successful-git-branching-model/)
with a few updates to better fit our workflow.

As we have to maintain multiple master copies of our plugins, instead of
a `master` branch, we maintain multiple master version branches. These branches
are named using the format `v#` where `v2` would represent all releases for
the `v2.x` branch of the software. These version numbers correlate with the
plugin version numbers, not the Craft version number, as we may have multiple
major releases of a plugin for a single release of Craft.

```
# Example plugin branches
- develop
- feature/feature-name
- bugfix/bugfix-name
- v1
- v2
- v3
```

## Local Development

Create a symlink to the plugin or module within the Craft project that will be
used for development:

```
$ ln -s /path/to/cloned/repo /path/to/symlink/in/project/vendor/barrelstrength/folder
```

In the case you will be editing front-end assets, you'll need to configure `npm`
and process the `scss` and `js` files you edit in a give asset
bundle's `src/web/[assetbundle]/src` folder into
the `src/web/[assetbundle]/dist` folder:

```
npm install
npm run watch
```  

## Naming

The name of a plugin will be used in several different contexts. We use the
following conventions:

| Context | Naming Convention |
|:----- |:----------------- |
| GitHub repo | barrelstrength/craft-sprout-forms |
| Composer/Packagist | barrelstrength/sprout-forms |
| Plugin folder | barrelstrength/sprout-forms |
| Namespace | barrelstrength/sproutforms |

## Folder Structure

When possible, we follow conventions in Craft's folder architecture in our
plugins.

### Root directory and key src files

```
├── .github
├── .gitignore
├── CHANGELOG-[MODULE].md
├── composer.json
├── lib
├── LICENSE.md
├── README.md
└── src
    ├── Plugin.php
    └── translations
        └── en
└── webpack.mix.js
```

References within `README.md` and any other general information files should be
kept to a minimum and point users toward our docs, where we maintain more
comprehensive documentation. As we maintain several plugins, it gets tedious to
update references in numerous general information files and our documentation
serves as a centralized place for this type of information about our plugins.

Each module has it's own `CHANGELOG-[MODULE].md` file. These are the canonical
sources for documenting changes and this data will be copied to a
plugin's `CHANGELOG.md` file for each module used by a plugin.

Similarly, we aim to keep the `composer.json` file as simple as possible. Don't
add `schemaVersion`, `hasCpSection`, or `hasCpSettings` to this file. They
should go in the primary plugin module class to more easily toggle the settings
without running into issues with cached values in Craft's `plugins.php` file.

We rename the primary module class `Plugin.php` to use the name of the plugin (
i.e. `SproutForms.php`). This update requires that we set the `composer.json` _
extra->class_ setting to define the Plugin.php as a file with the name of the
plugin itself.

Front-end packages are managed in `package.json`. Third-party libraries that
must be included in the plugin are are added to the `lib` folder. CSS (SCSS) and
Javascript (ES6) assets are managed with Webpack via Laravel Mix. Each Asset
Source is configured in `webpack.mix.js` to process files from
the `src/web/[assetbundle]/src` folder and compile them to
the `src/web/[assetbundle]/dist` folder.

Four npm scripts are available during development:

- **watch** - Watches for changes in resources and builds production `dist/`
  files
- **build** - Builds production `dist/` files once
- **debug** - Watches for changes in resources and builds dev `dist/` files
- **dev** - Builds dev `dist/` files once

The `dist/` files built for production and dev have the same filenames, so it is
a convention that we only commit production files to the repository. The `debug`
and `dev` commands can be used when debugging.

### Resources and templating

```
└── src
    └── web
        └── assets
            └── [assetbundle]
                ├── dist
                ├── src
                └── [CustomAssetBundle].php    
        └── twig
            └── variables
                └── [CustomVariable.php]
```

All asset bundles are managed in the `src/web/assets` folder and all things
Twig (variables, filters, nodes, etc.) are managed in the `src/web/twig` folder.

### Components and Integrations

```
└── src
    └── base
        └── [BaseCustomComponentType].php
    └── [customcomponenttype]
        ├── [CustomComponentType1].php
        └── [CustomComponentType2].php
    └── fields
        ├── [CustomField1].php
        └── [CustomField2].php
    └── integrations
        └── [pluginname]
            └── [integrationcustomcomponenttype]
                ├── [IntegrationCustomComponentType1].php
                └── [IntegrationCustomComponentType2].php
    └── templates 
        └── _components
            └── [customcomponenttype]
                ├── [customcomponenttype1]
                └── [customcomponenttype2]
            └── fields
                ├── [customfield1]
                └── [customfield2]
        └── _integrations
            └── [pluginname]
                └── [integrationcustomcomponenttype]
                    ├── [integrationcustomcomponenttype1]
                    └── [integrationcustomcomponenttype2]
```

We manage various types of components within our plugins. The structure above
illustrates how we organize three classes of components. We use the example of a
Custom Field as how we would organize extending Craft component within our
plugins.

| Component Type | Name | Notes |
|:---------- |:----- |:------ |
| Craft Components | `fields` | Craft component classes get placed in a folder named after the component type in the `src` folder |
| Custom Components | `[customcomponenttype]` | Custom Component classes get placed in a folder named after the component type in the `src` folder |
| Integration Components | `[integrationcustomcomponenttype]` | Integration Component classes get placed in a folder named after the component type in the `integrations/pluginname` folder |

### Github community templates

```
└── .github
    ├── CODE_OF_CONDUCT.md
    ├── CONTRIBUTING.md
    └── ISSUE_TEMPLATE
        bug-report.md
        feature-request.md
    ├── SECURITY.md
    └── SUPPORT.md
```

Community templates should link to our pages in our documentation with more
comprehensive information wherever possible.

### Changelog Template

#### Plugin CHANGELOG

```
# Changelog

## X.Y.Z - YYYY-MM-DD

> **Note|Tip|Warning**
> Short summary of key release details

### Updated
- Requires `barrelstrength/craft-sprout:vX.Y.Z`
- See Forms Module [release notes]([PERMALINK])
- See Data Studio Module [release notes]([PERMALINK])
```

#### Sprout Module CHANGELOG

```
# Changelog

## 2.x - UNRELEASED

### Added
- Added...

### Changed
- Updated/Improved/Removed...

### Fixed
- Fixed...
```

## Common Modules

Many Sprout plugins share functionality and this code is managed in shared Yii
Modules.

| Module | Description | 
|:----- |:----------------- |
| barrelstrength/sprout-base | Common settings and UI components |
| barrelstrength/sprout-base-email | Common email functionality | 
| barrelstrength/sprout-base-fields | Common field functionality |
| barrelstrength/sprout-base-reports | Common reporting functionality |
| barrelstrength/sprout-base-redirects | Common sitemap functionality |
| barrelstrength/sprout-base-sent-email | Common sent email functionality |
| barrelstrength/sprout-base-sitemaps | Common redirects functionality |
| barrelstrength/sprout-base-uris | Common URL-enabled Section functionality |

## Sprout Settings

Sprout plugins have a custom settings API to manage settings pages. This assists
in managing shared settings between plugins as well as managing the UI in the
control panel for more advanced settings pages.

Additionally, Sprout Plugins that use the API should disable the default Craft
settings behavior so their icon does not appear in the Craft settings listing
and instead register their `SproutSettings` class to display in the 'Sprout
Settings' section of the Settings are.

There are two types of settings

- Simple settings - They use our standard shares settings pages, provide simple
  fields to be saved as settings, save those settings to the `sprout-settings`
  key in the Project Config, and allow the overriding of those settings via
  the `config/sprout-settings.php` file.

As Sprout plugins share several core modules, we use a custom settings API

Extend the base `barrelstrength/sproutbase/base/SproutSettings` class

Register all settings and shared settings SproutSettings types in the plugin
that uses them. If more than one plugin uses the same settings, they will only
get registered once.

``` php
Event::on(Settings::class, Settings::EVENT_REGISTER_SPROUT_SETTINGS, static function(RegisterComponentTypesEvent $event) {
    $event->types[] = FormsSettings::class;
    $event->types[] = NotificationsSettings::class;
    $event->types[] = ReportsSettings::class;
});
```

## Uninstall Migrations

As the Sprout Plugins depend on several shared modules, we need to make sure no
shared data is removed when uninstalling a plugin. To do this, plugins using
shared modules should implement `SproutDependencyInterface`
and `SproutDependencyTrait`.

``` php
use barrelstrength\sproutbase\base\SproutDependencyInterface;
use barrelstrength\sproutbase\base\SproutDependencyTrait;

class SproutForms extends Plugin implements SproutDependencyInterface
{
    use SproutDependencyTrait;

    public function getSproutDependencies(): array
    {
        return [
            SproutDependencyInterface::SPROUT_BASE,
            SproutDependencyInterface::SPROUT_BASE_REPORTS
        ];
    }
}
```

And then check for any shared dependencies before performing uninstall actions.

``` php
use barrelstrength\sproutbase\base\SproutDependencyInterface;
use barrelstrength\sproutbasereports\migrations\Install as SproutBaseReportsInstall;
use barrelstrength\sproutreports\SproutReports;
use craft\db\Migration;

class Install extends Migration
{
    public function safeDown(): bool
    {
        $sproutBaseReportsInUse = SproutReports::getInstance()->dependencyInUse(SproutDependencyInterface::SPROUT_BASE_REPORTS);

        if (!$sproutBaseReportsInUse) {
            $migration = new SproutBaseReportsInstall();

            ob_start();
            $migration->safeDown();
            ob_end_clean();
        }

        return true;
    }
}
```

## Translations

Use the default Craft conventions for translations. This allows us to benefit
from the Yii Inspections that allow us to bulk add and remove translations.

| Context | Translation Convention |
|:-------- |:---------------------- |
| PHP      | `Craft::t('sprout-forms', 'Message');` |
| Twig     | `{{ "Message"|t('sprout-forms') }}` | 
| CP Javascript | `Craft:t('sprout-forms');` |

Each plugin or module maintains it's own translation file. As some plugins
depend on multiple modules for their functionality, this may mean that someone
translating a plugin will also have to translate translation files in other
modules. For example, to completely translate Sprout Forms one would need to
translate the files in Sprout Forms, Sprout Base Fields, and Sprout Base.

### Exceptions

Exceptions are for developers, not for users. Exception messages should not be
translated:

```
BAD:
throw new Exception(Craft::t('sprout-forms, 'Something happened'));

GOOD:
throw new Exception('Something happened');
```

## Migrations

Due to our application structure using shared modules, in some cases migrations
may need to be run by multiple plugins and we cannot know which order they will
get run in. To address this, we use the following conventions:

- Make sure every migration can be run twice, without throwing errors if it has
  already been run once.
- All migrations that affect a plugin with a shared module should be placed in
  the base module and instances of those migrations should be created in each
  respective plugin where they are needed.

    - Docs: 'Call to Craft or Sprout service layer' inspection
    - Database Audit Utility notes...

### Naming migrations

#### Order

Migration naming will use the date in the first segment and the second segment
will just represent the order that they should be run in for a particular
release. The following migrations are all be part of a release on the same day,
and are ordered 1, 2, 3 in the order they should run:

```
m190101_000001_migration_description.php
m190101_000002_migration_description.php
m190101_000003_migration_description.php
```

#### Migrations used by multiple plugins

Any migration instance that is just running a migration in base module should
use the same name as the base migration and append the plugin name that it is
being run from.

```
m190101_000001_migration_description.php              // Sprout Base Email
m190101_000001_migration_description_sproutemail.php  // Sprout Email
m190101_000001_migration_description_sproutforms.php  // Sprout Forms
```

### Testing Prior to Release

To test one or more plugins and modules under development on real websites
before releases, changes can be pushed to a development branch and pulled into
any appropriate project for testing.

In this example, we grab the latest on the `develop` branch for the Sprout SEO
plugin and the Sprout Base Redirects module. To ensure composer things it's
working with the release numbers we're using in our `composer.json` we can tell
composer what version number to use for the code we are testing:

```
composer require barrelstrength/sprout-seo:"dev-develop as 4.2.0" barrelstrength/sprout-base-redirects:"dev-develop as 1.1.1"
```

Requiring `dev-develop` should pull in the latest commit on the `develop`
branch. For more specific tests, you can also target a specific commit hash:

```
composer require barrelstrength/sprout-seo:"dev-develop as 4.2.0" barrelstrength/sprout-base-redirects:"dev-develop#dfae1a922cdb5dd32fd8a813839fddc26ff412b0 as 1.1.1"
```

For additional troubleshooting, consider some of the following steps:

```
rm composer.lock      # Remove the composer.lock file
composer clear-cache  # Clear composers cache
composer remove ...   # Try removing the package you are testing before installing it
rm -r ./vendor        # Remove the entire ./vendor directory and rebuild it with `composer update`
```
