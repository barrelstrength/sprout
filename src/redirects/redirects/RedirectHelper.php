<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\redirects\db\SproutTable;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;

class RedirectHelper
{
    public const SLASH_CHARACTER = '/';

    public static function removeSlash(string $uri = null): ?string
    {
        if ($uri === null) {
            return null;
        }

        // If the first character of the URI is a slash, remove it
        if (isset($uri[0]) && $uri[0] == self::SLASH_CHARACTER) {
            $uri = ltrim($uri, self::SLASH_CHARACTER);
        }

        return $uri;
    }

    public static function getStructureId(): int
    {
        return RedirectsModule::getInstance()->getSettings()->getStructureId();
    }

    /**
     * Get Redirect status codes
     */
    public static function getStatusCodes(): array
    {
        $statusCodes = [
            Craft::t('sprout-module-redirects', StatusCode::PERMANENT) => 'Permanent',
            Craft::t('sprout-module-redirects', StatusCode::TEMPORARY) => 'Temporary',
            Craft::t('sprout-module-redirects', StatusCode::PAGE_NOT_FOUND) => 'Page Not Found',
        ];

        $newStatusCodes = [];

        foreach ($statusCodes as $key => $value) {
            $value = preg_replace('#([a-z])([A-Z])#', '$1 $2', $value);
            $newStatusCodes[$key] = $key . ' - ' . $value;
        }

        return $newStatusCodes;
    }

    /**
     * Update the current statusCode in the record
     */
    public static function updateStatusCode($ids, $statusCode): int
    {
        return Craft::$app->db->createCommand()->update(
            SproutTable::REDIRECTS,
            ['statusCode' => $statusCode],
            ['in', 'id', $ids]
        )->execute();
    }
}
