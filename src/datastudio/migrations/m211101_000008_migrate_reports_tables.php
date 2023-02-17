<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000008_migrate_reports_tables extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';
    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';

    public const OLD_DATASOURCES_TABLE = '{{%sproutreports_datasources}}';
    public const OLD_REPORTS_TABLE = '{{%sproutreports_reports}}';
    public const OLD_REPORTS_GROUPS_TABLE = '{{%sproutreports_reportgroups}}';

    public const DATA_SET_ELEMENT_CLASS = 'BarrelStrength\Sprout\datastudio\components\elements\DataSetElement';

    public function safeUp(): void
    {
        $newSourceGroupIds = [];

        $cols = [
            'id',
            'name',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_REPORTS_GROUPS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_REPORTS_GROUPS_TABLE])
                ->all();

            foreach ($rows as $row) {
                $row['type'] = self::DATA_SET_ELEMENT_CLASS;

                // Don't insert old ID since we're merging multiple things into Source Groups
                $oldId = $row['id'];
                unset($row['id']);

                $this->insert(self::SOURCE_GROUPS_TABLE, $row);
                $newSourceGroupId = $this->db->getLastInsertID(self::SOURCE_GROUPS_TABLE);

                $this->update(self::OLD_REPORTS_TABLE, [
                    'groupId' => $newSourceGroupId,
                ], [
                    'groupId' => $oldId,
                ], [], false);

                $newSourceGroupIds[] = $newSourceGroupId;
            }
        }

        $oldTableCols = [
            'id',
            'groupId',
            'name',
            'nameFormat',
            'handle',
            'description',
            'allowHtml',
            'sortOrder',
            'sortColumn',
            'delimiter',
            'dataSourceId',
            'settings',
            'enabled',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $newTableCols = [
            'id',
            'groupId',
            'name',
            'nameFormat',
            'handle',
            'description',
            'allowHtml',
            'sortOrder',
            'sortColumn',
            'delimiter',
            'settings',
            'enabled',
            'dateCreated',
            'dateUpdated',
            'uid',
            'type',
        ];

        if ($this->getDb()->tableExists(self::OLD_DATASOURCES_TABLE)) {

            $dataSources = (new Query())
                ->select('*')
                ->from([self::OLD_DATASOURCES_TABLE])
                ->all();

            $dataSourcesTypes = array_column($dataSources, 'type', 'id');

            $rows = (new Query())
                ->select($oldTableCols)
                ->from([self::OLD_REPORTS_TABLE])
                ->all();

            foreach ($rows as $key => $row) {
                if (!in_array($row['groupId'], $newSourceGroupIds, true)) {
                    $rows[$key]['groupId'] = null;
                }
                $rows[$key]['allowHtml'] = (bool)($row['allowHtml'] ?? false);

                if (isset($dataSourcesTypes[$row['dataSourceId']])) {
                    $rows[$key]['type'] = $dataSourcesTypes[$row['dataSourceId']];
                } else {
                    // Remove from migration if No Data Source Type found
                    unset($rows[$key]);
                }

                unset($rows[$key]['dataSourceId']);
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::DATASETS_TABLE, $newTableCols, $rows)
                ->execute();
        }

        // Remove retired Data Source
        $elementIds = (new Query())
            ->select(['id'])
            ->from([self::DATASETS_TABLE])
            ->where([
                'type' => 'barrelstrength\sproutreportscategories\integrations\sproutreports\datasources\Categories',
            ])
            ->column();

        $this->delete(Table::ELEMENTS, ['in', 'id', $elementIds]);
    }

    public function safeDown(): bool
    {
        echo "m211101_000008_migrate_reports_tables cannot be reverted.\n";

        return false;
    }
}
