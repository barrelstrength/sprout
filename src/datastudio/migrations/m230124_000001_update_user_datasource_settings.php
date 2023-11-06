<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m230124_000001_update_user_datasource_settings extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';
    public const USER_DATA_SOURCE_CLASS = 'BarrelStrength\Sprout\datastudio\components\datasources\UsersDataSource';

    public function safeUp(): void
    {
        $userDataSets = (new Query())
            ->select([
                'id',
                'settings',
            ])
            ->from([self::DATASETS_TABLE])
            ->where([
                'type' => self::USER_DATA_SOURCE_CLASS,
            ])
            ->all();

        foreach ($userDataSets as $userDataSet) {
            if (!$newSettings = Json::decode($userDataSet['settings'])) {
                continue;
            }

            $newSettings['userGroupIds'] = $newSettings['userGroups'] ?? [];
            unset($newSettings['userGroups']);

            $this->update(self::DATASETS_TABLE, [
                'settings' => Json::encode($newSettings),
            ], ['id' => $userDataSet['id']], [], true);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
