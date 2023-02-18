<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\SproutSettings;
use Craft;
use craft\helpers\StringHelper;

trait SproutModuleTrait
{
    /**
     * Returns true if a Sprout module is enabled
     */
    public static function isEnabled(): bool
    {
        // NOTE: Do not make this method static, it instantiates the Sprout Core
        // module which is needed to register sprout-module-core translations
        // for all modules in the suite.
        $enabledModules = Sprout::getInstance()->coreModules->getEnabledModules();

        return in_array(static::class, $enabledModules, true);
    }

    /**
     * Returns true if a Sprout module has editions
     */
    public static function hasEditions(): bool
    {
        return self::hasTrait(self::class, EditionTrait::class);
    }

    /**
     * Returns true if a Sprout module has migrations
     */
    public static function hasMigrations(): bool
    {
        return self::hasTrait(self::class, MigrationTrait::class);
    }

    /**
     * Returns the registered Yii module ID for a Sprout module
     * Also used as the category for translations and path for project config settings
     *
     * @example sprout-module-forms
     */
    public static function getModuleId(): string
    {
        return 'sprout-module-' . static::getShortName();
    }

    /**
     * Returns the short name without any dashes for use in namespaces and file paths
     *
     * @example forms => forms
     * @example data-studio => datastudio
     */
    public static function getShortNameSlug(): string
    {
        return str_replace('-', '', static::getShortName());
    }

    /**
     * Returns the prefix used for environment variables
     *
     * @example SPROUT_MODULE_FORMS_ where SPROUT_MODULE_FORMS_SETTING_NAME
     * will automatically override the matching camel case settingName
     */
    public static function getEnvPrefix(): string
    {
        return strtoupper(StringHelper::toSnakeCase(static::getModuleId())) . '_';
    }

    /**
     * Returns the project config path
     *
     * @example sprout.sprout-module-forms
     * @example sprout.sprout-module-forms.[path]
     */
    public static function projectConfigPath(string $path = null): string
    {
        $projectConfigBasePath = SproutSettings::ROOT_PROJECT_CONFIG_KEY . '.' . static::getModuleId();

        if (!$path) {
            return $projectConfigBasePath;
        }

        return $projectConfigBasePath . '.' . $path;
    }

    /**
     * Returns an svg
     *
     * @example sprout/assets/dist/static/forms
     * @example sprout/assets/dist/static/forms/[path]
     */
    public static function svg($path = null): string
    {
        $path = $path ? DIRECTORY_SEPARATOR . $path : null;

        return Craft::getAlias('@Sprout/Assets' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . static::getShortName() . $path);
    }

    /**
     * Returns a permission name
     *
     * @example sprout-module-forms:editForms
     */
    public static function p(string $name, bool $lowercase = false): string
    {
        $permissionName = static::getModuleId() . ':' . $name;

        if ($lowercase) {
            return strtolower($permissionName);
        }

        return $permissionName;
    }

    /**
     * Returns true if a class uses a given trait
     */
    private static function hasTrait($class, string $trait): bool
    {
        return in_array($trait, class_uses($class), true);
    }
}
