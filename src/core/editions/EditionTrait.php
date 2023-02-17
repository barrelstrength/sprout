<?php

namespace BarrelStrength\Sprout\core\editions;

use Craft;
use craft\helpers\UrlHelper;

trait EditionTrait
{
    /**
     * The edition that this module has been granted
     */
    static public string $edition = Edition::LITE;

    /**
     * Check if a module can be upgraded using !$module::isPro()
     *
     * Use this method for conditionals, regardless of
     * whether the user is an admin or not
     */
    public static function isPro(): bool
    {
        return static::$edition === Edition::PRO;
    }

    /**
     * Determines if we should display upgrade messages
     *
     * Use this method for conditionals when displaying
     * upgrade messages in the UI
     */
    public static function isUpgradable(): bool
    {
        $isAdmin = Craft::$app->getUser()->getIsAdmin();
        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return !$isAdmin && $allowAdminChanges && static::$edition !== Edition::PRO;
    }

    /**
     * Returns the URL to the upgrade page for a module
     */
    public static function getUpgradeUrl(): string
    {
        return UrlHelper::cpUrl('sprout/upgrade/' . static::getShortName());
    }

    /**
     * Grant a module the highest edition available
     */
    public function grantEdition(string $edition = null): void
    {
        if (static::$edition === Edition::PRO) {
            return;
        }

        if (static::$edition === Edition::STANDARD) {
            return;
        }

        static::$edition = $edition ?? Edition::LITE;
    }
}
