<?php

namespace BarrelStrength\Sprout\redirects\migrations\helpers;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\records\Structure;

class RedirectStructureHelper
{
    public const REDIRECT_MODULE_SETTINGS_KEY = 'sprout.sprout-module-redirects';

    public static function updateStructureInDbToMatchUidInProjectConfig(
        string $structureUidFromConfig,
        string $structureUidInDb
    ): void {
        if ($structureUidFromConfig === $structureUidInDb) {
            return;
        }

        // We  update the UID of the Structure Element we found or created
        // in the DB to match the one in the Project Config
        Craft::$app->getDb()->createCommand()->update(Table::STRUCTURES, [
            'uid' => $structureUidFromConfig,
        ], [
            'uid' => $structureUidInDb,
        ])->execute();
    }

    public static function getStructureUidFromDb(string $structureUidFromConfig = null): ?string
    {
        // Can we just use the Structure UID from the config?
        if ($structureUidFromConfig) {
            $structureUidInConfigExistsInDb = (new Query())
                ->select('uid')
                ->from(Table::STRUCTURES)
                ->where([
                    'uid' => $structureUidFromConfig,
                ])
                ->exists();

            if ($structureUidInConfigExistsInDb) {
                return $structureUidFromConfig;
            }
        }

        // Make sure we don't have some other Structure in use in the db
        if ($existingStructureUidInDbUsedByRedirects = self::getStructureUidFromRedirectElement()) {
            return $existingStructureUidInDbUsedByRedirects;
        }

        // Create a new Structure
        return self::createStructureAndGetUid();
    }

    public static function createStructureAndGetUid(): ?string
    {
        $structure = new Structure();
        $structure->maxLevels = 1;

        if (!$structure->save()) {
            return null;
        }

        return $structure->uid;
    }

    public static function getStructureUidFromRedirectElement(): ?string
    {
        $redirectElement = RedirectElement::find()->one();

        if (!$redirectElement) {
            return null;
        }

        $oldStructureId = is_int($redirectElement->structureId)
            ? $redirectElement->structureId
            : null;

        if (!$oldStructureId) {
            return null;
        }

        return (new Query())
            ->select('uid')
            ->from(Table::STRUCTURES)
            ->where([
                'id' => $oldStructureId,
            ])
            ->scalar();
    }
}
