<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use craft\base\SavableComponentInterface;
use craft\config\BaseConfig;
use yii\base\Module;

/**
 * @mixin Module
 */
interface SproutModuleInterface
{
    public static function isEnabled(): bool;

    public static function hasEditions(): bool;

    public static function hasMigrations(): bool;

    public static function getModuleId(): string;

    public static function getDisplayName(): string;

    public static function getShortName(): string;

    public function createSettingsModel(): ?BaseConfig;

    public static function projectConfigPath(string $path = null): string;

    public static function getEnvPrefix(): string;
}
