<?php

namespace BarrelStrength\Sprout\forms\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;

/**
 * This migration must come after the Reports migration as
 * we need to insert default data source settings
 */
class m211101_000007_migrate_forms_tables extends Migration
{
    public const FORMS_TABLE = '{{%sprout_forms}}';
    public const FORM_INTEGRATIONS_TABLE = '{{%sprout_form_integrations}}';
    public const FORM_INTEGRATIONS_LOG_TABLE = '{{%sprout_form_integrations_log}}';
    public const FORM_SUBMISSIONS_STATUSES_TABLE = '{{%sprout_form_submissions_statuses}}';
    public const FORM_SUBMISSIONS_TABLE = '{{%sprout_form_submissions}}';
    public const FORM_SUBMISSIONS_SPAM_LOG_TABLE = '{{%sprout_form_submissions_spam_log}}';

    public const OLD_FORMS_TABLE = '{{%sproutforms_forms}}';
    public const OLD_FORM_GROUPS_TABLE = '{{%sproutforms_formgroups}}';
    public const OLD_FORM_INTEGRATIONS_TABLE = '{{%sproutforms_integrations}}';
    public const OLD_FORM_INTEGRATIONS_LOG_TABLE = '{{%sproutforms_integrations_log}}';
    public const OLD_SUBMISSIONS_TABLE = '{{%sproutforms_entries}}';
    public const OLD_FORM_SUBMISSIONS_STATUSES_TABLE = '{{%sproutforms_entrystatuses}}';
    public const OLD_FORM_SUBMISSIONS_SPAM_LOG_TABLE = '{{%sproutforms_entries_spam_log}}';

    public const FORM_ELEMENT_CLASS = 'BarrelStrength\Sprout\forms\components\elements\FormElement';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'name',
            'handle',
            'color',
            'sortOrder',
            'isDefault',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_FORM_SUBMISSIONS_STATUSES_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_FORM_SUBMISSIONS_STATUSES_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORM_SUBMISSIONS_STATUSES_TABLE, $cols, $rows)
                ->execute();
        }

        $cols = [
            'id',
            'fieldLayoutId', // Convert to submissionFieldLayout config
            'name',
            'handle',
            'titleFormat',
            'displaySectionTitles',
            'redirectUri',
            'submissionMethod',
            'errorDisplayMethod',
            'successMessage', // messageOnSuccess
            'errorMessage', // messageOnError
            'submitButtonText',
            'saveData',
            'enableCaptchas',
            'dateCreated',
            'dateUpdated',
            'uid',

            'formTemplateId', // @todo - create form type and insert UID
        ];

        $colsNew = [
            'id',

            'name',
            'handle',
            'titleFormat',
            'displaySectionTitles',
            'redirectUri',
            'submissionMethod',
            'errorDisplayMethod',
            'messageOnSuccess', // successMessage
            'messageOnError', // errorMessage
            'submitButtonText',
            'saveData',
            'enableCaptchas',
            'dateCreated',
            'dateUpdated',
            'uid',

            'submissionFieldLayout', // Convert from fieldLayoutId and remove fieldLayoutId column
            'formTypeUid',
        ];

        if ($this->getDb()->tableExists(self::OLD_FORMS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_FORMS_TABLE])
                ->all();

            foreach ($rows as $key => $row) {
                if (isset($row[$key]['fieldLayoutId'])) {
                    $layoutId = $row[$key]['fieldLayoutId'];
                    $layout = Craft::$app->getFields()->getLayoutById($layoutId);

                    if ($layout) {
                        // @todo - review. Is this all we need to do?
                        $row[$key]['submissionFieldLayout'] = Json::encode($layout->getConfig());
                    }
                }

                /** @todo - figure out formTemplateUid */
                $rows[$key]['formTemplateUid'] = 'REPLACE_ME';

                unset(
                    $rows[$key]['fieldLayoutId'],
                    $rows[$key]['formTemplateId'],
                );
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORMS_TABLE, $colsNew, $rows)
                ->execute();

            $this->createFormContentTables($rows);
        }

        $cols = [
            'id',
            'formId',
            'statusId',
            'ipAddress',
            'referrer',
            'userAgent',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_SUBMISSIONS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_SUBMISSIONS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORM_SUBMISSIONS_TABLE, $cols, $rows)
                ->execute();
        }

        $cols = [
            'id',
            'entryId',
            'type',
            'errors',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_FORM_SUBMISSIONS_SPAM_LOG_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_FORM_SUBMISSIONS_SPAM_LOG_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORM_SUBMISSIONS_SPAM_LOG_TABLE, $cols, $rows)
                ->execute();
        }

        $cols = [
            'id',
            'formId',
            'name',
            'type',
            'sendRule',
            'settings',
            'enabled',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_FORM_INTEGRATIONS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_FORM_INTEGRATIONS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORM_INTEGRATIONS_TABLE, $cols, $rows)
                ->execute();
        }

        $cols = [
            'id',
            'entryId',
            'integrationId',
            'success',
            'status',
            'message',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_FORM_INTEGRATIONS_LOG_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_FORM_INTEGRATIONS_LOG_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::FORM_INTEGRATIONS_LOG_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    // @todo Does this work for migrating form content?
    public function createFormContentTables($formRows): void
    {
        foreach ($formRows as $form) {
            // If no form handle exists, keep moving
            if (!$formHandle = $form['handle'] ?? null) {
                continue;
            }

            // Establish our old table and new table names
            $oldContentTable = "{{%sproutformscontent_{$formHandle}}}";
            $newContentTable = "{{%sprout_formcontent_{$formHandle}}}";

            // If the new table already exists, carry on
            if ($this->db->tableExists($newContentTable)) {
                continue;
            }

            // Simplify the old table by removing indices and foreign keys
            Db::dropAllForeignKeysToTable($oldContentTable);

            //            @todo - drop all indexes. Need to do so one by one.
            //            Db::dropIndexIfExists($oldContentTable);

            // Rename the old table to the the new table name
            Db::renameTable($oldContentTable, $newContentTable);

            $this->createIndex(null, $newContentTable, ['elementId', 'siteId'], true);
            $this->addForeignKey(null, $newContentTable, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE');
            $this->addForeignKey(null, $newContentTable, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

            $this->dropTableIfExists($oldContentTable);
        }
    }
}
