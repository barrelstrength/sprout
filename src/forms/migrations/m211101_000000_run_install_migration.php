<?php

namespace BarrelStrength\Sprout\forms\migrations;

use BarrelStrength\Sprout\forms\migrations\helpers\InsertDefaultSubmissionStatuses;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-forms';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\forms\FormsModule';
    public const FORM_TEMPLATE_SET = 'BarrelStrength\Sprout\forms\components\formtemplates\DefaultFormTemplateSet';
    public const SPAM_REDIRECT_BEHAVIOR_NORMAL = 'redirectAsNormal';

    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';
    public const FORMS_TABLE = '{{%sprout_forms}}';
    public const FORM_INTEGRATIONS_TABLE = '{{%sprout_form_integrations}}';
    public const FORM_INTEGRATIONS_LOG_TABLE = '{{%sprout_form_integrations_log}}';
    public const FORM_SUBMISSIONS_STATUSES_TABLE = '{{%sprout_form_submissions_statuses}}';
    public const FORM_SUBMISSIONS_TABLE = '{{%sprout_form_submissions}}';
    public const FORM_SUBMISSIONS_SPAM_LOG_TABLE = '{{%sprout_form_submissions_spam_log}}';

    public const OLD_SUBMISSION_STATUSES_TABLE = '{{%sproutforms_submissionstatuses}}';

    public const SUBMISSION_INTEGRATION_PENDING_STATUS = 'pending';
    public const SUBMISSION_INTEGRATION_NOT_SENT_STATUS = 'notsent';
    public const SUBMISSION_INTEGRATION_COMPLETED_STATUS = 'completed';

    public const GREEN = 'green';
    public const ORANGE = 'orange';
    public const RED = 'red';
    public const BLUE = 'blue';
    public const YELLOW = 'yellow';
    public const PINK = 'pink';
    public const PURPLE = 'purple';
    public const TURQUOISE = 'turquoise';
    public const LIGHT = 'light';
    public const GREY = 'grey';
    public const BLACK = 'black';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = $moduleSettingsKey . '.modules.' . self::MODULE_CLASS;

        $this->createTables();

        if ($this->isNewInstallation()) {
            $defaultSubmissionStatusesMigration = new InsertDefaultSubmissionStatuses();
            ob_start();
            $defaultSubmissionStatusesMigration->safeUp();
            ob_end_clean();
        }

        // @todo - fix default settings to import
        Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
            'defaultSection' => 'submissions',
            'formTemplateId' => self::FORM_TEMPLATE_SET,
            'enableSaveData' => true,
            'saveSpamToDatabase' => false,
            'enableSaveDataDefaultValue' => true,

            // FormsSettings::SPAM_REDIRECT_BEHAVIOR_NORMAL
            'spamRedirectBehavior' => self::SPAM_REDIRECT_BEHAVIOR_NORMAL,
            'spamLimit' => 500,
            'cleanupProbability' => 1000,
            'trackRemoteIp' => false,
            'enableEditSubmissionViaFrontEnd' => false,
            // @todo - provide all settings. overwrites all nested settings.
            //            'captchaSettings' => [
            //                'BarrelStrength\Sprout\forms\components\captchas\DuplicateCaptcha' => [
            //                    'enabled' => false,
            //                ],
            //            ],
        ], "Update Sprout Module Settings for “{$moduleSettingsKey}”");

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'alternateName' => '',
            'enabled' => true,
        ], "Update Sprout CP Settings for “{$coreModuleSettingsKey}”");
    }

    public function safeDown(): bool
    {
        echo "m211101_000000_run_install_migration cannot be reverted.\n";

        return false;
    }

    /**
     * Returns true if install migration is being run as part of a fresh install
     *
     * Checks if the old, shared datasources table exists to determine if this is an upgrade migration
     */
    public function isNewInstallation(): bool
    {
        return !Craft::$app->getDb()->tableExists(self::OLD_SUBMISSION_STATUSES_TABLE);
    }

    protected function createTables(): void
    {
        if (!$this->getDb()->tableExists(self::FORMS_TABLE)) {
            $this->createTable(self::FORMS_TABLE, [
                'id' => $this->primaryKey(),
                'submissionFieldLayoutId' => $this->integer(),
                'groupId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'titleFormat' => $this->string()->notNull(),
                'displaySectionTitles' => $this->boolean()->notNull()->defaultValue(false),
                'redirectUri' => $this->string(),
                'submissionMethod' => $this->string()->notNull()->defaultValue('sync'),
                'errorDisplayMethod' => $this->string()->notNull()->defaultValue('inline'),
                'messageOnSuccess' => $this->text(),
                'messageOnError' => $this->text(),
                'submitButtonText' => $this->string(),
                'saveData' => $this->boolean()->notNull()->defaultValue(false),
                'formTemplateId' => $this->string(),
                'enableCaptchas' => $this->boolean()->notNull()->defaultValue(true),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORMS_TABLE, ['submissionFieldLayoutId']);
            $this->createIndex(null, self::FORMS_TABLE, ['name']);
            $this->createIndex(null, self::FORMS_TABLE, ['handle']);

            $this->addForeignKey(null, self::FORMS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::FORMS_TABLE, ['submissionFieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL');
            $this->addForeignKey(null, self::FORMS_TABLE, ['groupId'], self::SOURCE_GROUPS_TABLE, ['id'], 'SET NULL');
        }

        if (!$this->getDb()->tableExists(self::FORM_SUBMISSIONS_STATUSES_TABLE)) {
            $this->createTable(self::FORM_SUBMISSIONS_STATUSES_TABLE, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'color' => $this->enum('color', [
                    self::GREEN, self::ORANGE, self::RED,
                    self::BLUE, self::YELLOW, self::PINK,
                    self::PURPLE, self::TURQUOISE, self::LIGHT,
                    self::GREY, self::BLACK,
                ])->notNull()->defaultValue(self::BLUE),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'isDefault' => $this->boolean()->notNull()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORM_SUBMISSIONS_STATUSES_TABLE, ['name']);
            $this->createIndex(null, self::FORM_SUBMISSIONS_STATUSES_TABLE, ['handle']);
            $this->createIndex(null, self::FORM_SUBMISSIONS_STATUSES_TABLE, ['color']);
            $this->createIndex(null, self::FORM_SUBMISSIONS_STATUSES_TABLE, ['sortOrder']);
        }

        if (!$this->getDb()->tableExists(self::FORM_SUBMISSIONS_TABLE)) {
            $this->createTable(self::FORM_SUBMISSIONS_TABLE, [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'statusId' => $this->integer(),
                'ipAddress' => $this->string(),
                'referrer' => $this->string(),
                'userAgent' => $this->longText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORM_SUBMISSIONS_TABLE, ['formId']);
            $this->createIndex(null, self::FORM_SUBMISSIONS_TABLE, ['statusId']);

            $this->addForeignKey(null, self::FORM_SUBMISSIONS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::FORM_SUBMISSIONS_TABLE, ['formId'], self::FORMS_TABLE, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::FORM_SUBMISSIONS_TABLE, ['statusId'], self::FORM_SUBMISSIONS_STATUSES_TABLE, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::FORM_SUBMISSIONS_SPAM_LOG_TABLE)) {
            $this->createTable(self::FORM_SUBMISSIONS_SPAM_LOG_TABLE, [
                'id' => $this->primaryKey(),
                'submissionId' => $this->integer()->notNull(),
                'type' => $this->string(),
                'errors' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORM_SUBMISSIONS_SPAM_LOG_TABLE, ['submissionId']);

            $this->addForeignKey(null, self::FORM_SUBMISSIONS_SPAM_LOG_TABLE, ['submissionId'], self::FORM_SUBMISSIONS_TABLE, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::FORM_INTEGRATIONS_TABLE)) {
            $this->createTable(self::FORM_INTEGRATIONS_TABLE, [
                'id' => $this->primaryKey(),
                'formId' => $this->integer()->notNull(),
                'name' => $this->string()->notNull(),
                'type' => $this->string()->notNull(),
                'sendRule' => $this->text(),
                'settings' => $this->text(),
                'enabled' => $this->boolean()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORM_INTEGRATIONS_TABLE, ['formId']);
            $this->createIndex(null, self::FORM_INTEGRATIONS_TABLE, ['name']);

            $this->addForeignKey(null, self::FORM_INTEGRATIONS_TABLE, ['formId'], self::FORMS_TABLE, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::FORM_INTEGRATIONS_LOG_TABLE)) {
            $this->createTable(self::FORM_INTEGRATIONS_LOG_TABLE, [
                'id' => $this->primaryKey(),
                // Allow null. A Submission may not get saved to the database if saveData is set to false.
                'submissionId' => $this->integer(),
                'integrationId' => $this->integer()->notNull(),
                'success' => $this->boolean()->defaultValue(false),
                'status' => $this->enum('status', [
                    self::SUBMISSION_INTEGRATION_PENDING_STATUS,
                    self::SUBMISSION_INTEGRATION_NOT_SENT_STATUS,
                    self::SUBMISSION_INTEGRATION_COMPLETED_STATUS,
                ])->notNull()->defaultValue(self::SUBMISSION_INTEGRATION_PENDING_STATUS),
                'message' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::FORM_INTEGRATIONS_LOG_TABLE, ['submissionId']);
            $this->createIndex(null, self::FORM_INTEGRATIONS_LOG_TABLE, ['integrationId']);

            $this->addForeignKey(null, self::FORM_INTEGRATIONS_LOG_TABLE, ['submissionId'], self::FORM_SUBMISSIONS_TABLE, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::FORM_INTEGRATIONS_LOG_TABLE, ['integrationId'], self::FORM_SUBMISSIONS_TABLE, ['id'], 'CASCADE', 'CASCADE');
        }
    }
}
