<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m230217_000001_update_submission_datasource_settings extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';
    public const SUBMISSION_DATA_SOURCE_CLASS = 'BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource';

    public function safeUp(): void
    {
        $dataSets = (new Query())
            ->select([
                'id',
                'settings',
            ])
            ->from([self::DATASETS_TABLE])
            ->where([
                'type' => self::SUBMISSION_DATA_SOURCE_CLASS,
            ])
            ->all();

        foreach ($dataSets as $dataSet) {
            if (!$newSettings = Json::decode($dataSet['settings'])) {
                continue;
            }

            $newSettings['submissionStatusIds'] = $newSettings['entryStatusIds'] ?? [];
            unset($newSettings['entryStatusIds']);

            $this->update(self::DATASETS_TABLE, [
                'settings' => Json::encode($newSettings),
            ], ['id' => $dataSet['id']], [], true);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
