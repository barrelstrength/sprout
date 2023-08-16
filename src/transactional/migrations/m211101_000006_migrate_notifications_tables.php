<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\forms\components\emailthemes\FormSummaryEmailTheme;
use BarrelStrength\Sprout\mailer\components\emailthemes\CustomTemplatesEmailTheme;
use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class m211101_000006_migrate_notifications_tables extends Migration
{
    public const TRANSACTIONAL_EMAIL_TYPE = 'BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType';
    public const NEW_EMAIL_TABLE = '{{%sprout_emails}}';
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
        $oldEmailCols = [
            'id',
            'subjectLine',
            'defaultBody as defaultMessage', // => defaultMessage
            'emailTemplateId as emailThemeUid', // Already migrated to emailThemeUid
            'dateCreated',
            'dateUpdated',
            'uid',

            // Mailer
            'fromName', // => Approved Senders && mailerInstructionsSettings
            'fromEmail', // => Approved Senders && mailerInstructionsSettings
            'replyToEmail', // => Approved Reply To && mailerInstructionsSettings
            'recipients', // => mailerInstructionsSettings
            'cc', // Merge into recipients
            'bcc', // => sendMethod
            'listSettings', // => mailerInstructionsSettings Audience?
            //'sendMethod', // no need to migrate, standardized to use List Method

            // Email Type: Transactional, Notification Event Settings
            'eventId',
            'settings',
            'sendRule',
            'enableFileAttachments',
        ];

        $emailCols = [
            'id',
            'subjectLine',
            'defaultMessage',
            'emailThemeUid',
            'dateCreated',
            'dateUpdated',
            'uid',

            //'preheaderText', // No need to migrate, new setting

            // Email Type: Transactional
            'type',
            'emailTypeSettings',

            // Mailer: Transactional Mailer
            'mailerUid',
            'mailerInstructionsSettings',
        ];

        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {

            $defaultMailer = MailerHelper::getDefaultMailer();

            $rows = (new Query())
                ->select($oldEmailCols)
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            foreach ($rows as $key => $value) {

                $rows[$key]['type'] = self::TRANSACTIONAL_EMAIL_TYPE;

                $eventId = $rows[$key]['eventId'];
                $oldEventSettings = Json::decode($rows[$key]['settings'] ?? '[]');
                $sendRule = !empty($rows[$key]['sendRule']) ? $rows[$key]['sendRule'] : null;
                $eventSettings = $this->prepareEventSettings($eventId, $oldEventSettings, $sendRule);

                $rows[$key]['emailTypeSettings'] = Json::encode([
                    'eventId' => $rows[$key]['eventId'],
                    'eventSettings' => $eventSettings,
                    'enableFileAttachments' => $rows[$key]['enableFileAttachments'] ?? '',
                ]);

                $emailThemeMapping = [
                    'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates' => EmailMessageTheme::class,
                    'barrelstrength\sproutforms\integrations\sproutemail\emailtemplates\basic\BasicSproutFormsNotification' => FormSummaryEmailTheme::class,
                ];

                if ($matchingEmailThemeType = $emailThemeMapping[$rows[$key]['emailThemeUid']] ?? null) {
                    // Any mapped email themes should already be migrated
                    $emailTheme = MailerSchemaHelper::createEmailThemeIfNoTypeExists($matchingEmailThemeType);
                    $rows[$key]['emailThemeUid'] = $emailTheme->uid;
                }

                // merge bcc into recipients if cc not empty
                $recipients = $rows[$key]['recipients'] ?? '';
                $cc = $rows[$key]['cc'] ?? '';
                if (!empty($cc)) {
                    $recipients .= ',' . $cc;
                }
                $bcc = $rows[$key]['bcc'] ?? '';
                if (!empty($bcc)) {
                    $recipients .= ',' . $bcc;
                }

                $sender = $rows[$key]['fromName'] . ' <' . $rows[$key]['fromEmail'] . '>';

                $listSettings = Json::decode($rows[$key]['listSettings'] ?? '[]');
                $audienceIds = $listSettings['listIds'] ?? [];

                $rows[$key]['mailerUid'] = $defaultMailer->uid;
                $rows[$key]['mailerInstructionsSettings'] = Json::encode([
                    'sender' => $sender,
                    'replyToEmail' => $rows[$key]['replyToEmail'],
                    'recipients' => trim($recipients),
                    'audienceIds' => $audienceIds,
                ]);

                unset(
                    $rows[$key]['preheaderText'], // No need to migrate, new setting
                    $rows[$key]['fieldLayoutId'], // Migrated when CustomTemplateEmailTheme created
                    $rows[$key]['fromName'],
                    $rows[$key]['fromEmail'],
                    $rows[$key]['replyToEmail'],
                    $rows[$key]['recipients'],
                    $rows[$key]['cc'],
                    $rows[$key]['bcc'],
                    $rows[$key]['listSettings'],
                    $rows[$key]['eventId'],
                    $rows[$key]['settings'],
                    $rows[$key]['sendRule'],
                    $rows[$key]['enableFileAttachments'],
                );
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::NEW_EMAIL_TABLE, $emailCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function prepareEventSettings($eventId, $oldEventSettings, $sendRule): array
    {
        $conditionRules = [];

        switch ($eventId) {
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\EntrySavedNotificationEvent':
                $conditionClass = 'craft\elements\conditions\entries\EntryCondition';
                $conditionConfig = [
                    'elementType' => 'craft\elements\Entry',
                    'fieldContext' => 'global',
                ];

                // {"whenNew":"1","whenUpdated":"","sectionIds":["1"]}
                // {"whenNew":"1","whenUpdated":"","sectionIds":"*"}
                $whenNew = !empty($oldEventSettings['whenNew']) ? true : false;
                $whenUpdated = !empty($oldEventSettings['whenUpdated']) ? true : false;
                $sectionIds = $oldEventSettings['sectionIds'] ?? [];

                if ($whenNew) {
                    $ruleUid = StringHelper::UUID();
                    $conditionRules[] = [
                        'uid' => $ruleUid,
                        'class' => 'BarrelStrength\\Sprout\\transactional\\components\\conditions\\IsNewEntryConditionRule',
                        'type' => Json::encode([
                            'class' => 'BarrelStrength\\Sprout\\transactional\\components\\conditions\\IsNewEntryConditionRule',
                            'uid' => $ruleUid,
                            'value' => true,
                        ]),
                        'operator' => '',
                        'value' => '1',
                    ];
                }

                if ($whenUpdated) {
                    $ruleUid = StringHelper::UUID();
                    $conditionRules[] = [
                        'uid' => $ruleUid,
                        'class' => 'BarrelStrength\\Sprout\\transactional\\components\\conditions\\IsUpdatedEntryConditionRule',
                        'type' => Json::encode([
                            'class' => 'BarrelStrength\\Sprout\\transactional\\components\\conditions\\IsUpdatedEntryConditionRule',
                            'uid' => $ruleUid,
                            'value' => true,
                        ]),
                        'operator' => '',
                        'value' => '1',
                    ];
                }

                // Do nothing. No condition rule will send to ALL sections.
                //if ($sectionIds === '*') { }

                if (!empty($sectionIds) && $sectionIds !== '*' && count($sectionIds)) {

                    $sectionUids = (new Query())
                        ->select(['uid'])
                        ->from([Table::SECTIONS])
                        ->where(['id' => $sectionIds])
                        ->column();

                    $ruleUid = StringHelper::UUID();
                    $conditionRules[] = [
                        'uid' => $ruleUid,
                        'class' => 'craft\\elements\\conditions\\entries\\SectionConditionRule',
                        'type' => Json::encode([
                            'class' => 'craft\\elements\\conditions\\entries\\SectionConditionRule',
                            'uid' => $ruleUid,
                            'operator' => 'in',
                            'values' => $sectionUids,
                        ]),
                        'operator' => 'in',
                        'values' => $sectionUids,
                    ];
                }

                break;
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\EntryDeletedNotificationEvent':
                $conditionClass = 'craft\elements\conditions\entries\EntryCondition';
                $conditionConfig = [
                    'elementType' => 'craft\elements\Entry',
                    'fieldContext' => 'global',
                ];

                $ruleUid = StringHelper::UUID();
                $conditionRules[] = [
                    'uid' => $ruleUid,
                    'class' => 'BarrelStrength\Sprout\transactional\components\conditions\DraftConditionRule',
                    'type' => Json::encode([
                        'class' => 'BarrelStrength\Sprout\transactional\components\conditions\DraftConditionRule',
                        'uid' => $ruleUid,
                        'value' => false,
                    ]),
                    'operator' => '',
                    'value' => '',
                ];

                $ruleUid = StringHelper::UUID();
                $conditionRules[] = [
                    'uid' => $ruleUid,
                    'class' => 'BarrelStrength\Sprout\transactional\components\conditions\RevisionConditionRule',
                    'type' => Json::encode([
                        'class' => 'BarrelStrength\Sprout\transactional\components\conditions\DraftConditionRule',
                        'uid' => $ruleUid,
                        'value' => false,
                    ]),
                    'operator' => '',
                    'value' => '',
                ];

                break;
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\UserCreatedNotificationEvent':
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\UserUpdatedNotificationEvent':
                $conditionClass = 'craft\elements\conditions\users\UserCondition';
                $conditionConfig = [
                    'elementType' => 'craft\elements\User',
                    'fieldContext' => 'global',
                ];

                // When new and When updated are already sorted out when the Event is assigned.
                // {"whenNew":"","whenUpdated":"1","userGroupIds":"*","adminUsers":""}
                // {"whenNew":"","whenUpdated":"1","userGroupIds":["1"],"adminUsers":"1"}

                $adminUsers = !empty($oldEventSettings['adminUsers']) ? true : false;
                $userGroupIds = !empty($oldEventSettings['userGroupIds']) ? $oldEventSettings['userGroupIds'] : null;

                // Only migrate admin setting if no $sectionIds are selected
                if ($adminUsers && empty($userGroupIds)) {
                    $ruleUid = StringHelper::UUID();
                    $conditionRules[] = [
                        'uid' => $ruleUid,
                        'class' => 'craft\elements\conditions\users\AdminConditionRule',
                        'type' => Json::encode([
                            'class' => 'craft\elements\conditions\users\AdminConditionRule',
                            'uid' => $ruleUid,
                            'value' => true,
                        ]),
                        'operator' => '',
                        'value' => '1',
                    ];
                }

                if (!empty($userGroupIds) && $userGroupIds !== '*') {
                    $ruleUid = StringHelper::UUID();

                    $groupUids = (new Query())
                        ->select(['uid'])
                        ->from([Table::USERGROUPS])
                        ->where(['id' => $userGroupIds])
                        ->column();

                    $conditionRules[] = [
                        'uid' => $ruleUid,
                        'class' => 'craft\elements\conditions\users\GroupConditionRule',
                        'type' => Json::encode([
                            'class' => 'craft\elements\conditions\users\GroupConditionRule',
                            'uid' => $ruleUid,
                            'operator' => 'in',
                            'values' => $groupUids,
                        ]),
                        'operator' => 'in',
                        'values' => $groupUids,
                    ];
                }

                break;
            case 'BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent':
                $conditionClass = 'BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionCondition';
                $conditionConfig = [
                    'elementType' => 'BarrelStrength\Sprout\forms\components\elements\SubmissionElement',
                    'fieldContext' => 'global',
                ];

                // {"whenNew":"1","whenUpdated":"","formIds":["5"]} // No "All" scenario
                $whenNew = $oldEventSettings['whenNew'] ?? '';
                $whenUpdated = $oldEventSettings['whenUpdated'] ?? '';
                $sectionIds = $oldEventSettings['formIds'] ?? [];

                $ruleUid = StringHelper::UUID();
                $conditionRules[] = [
                    'uid' => $ruleUid,
                    'class' => 'BarrelStrength\Sprout\forms\components\elements\conditions\FormConditionRule',
                    'type' => Json::encode([
                        'class' => 'BarrelStrength\Sprout\forms\components\elements\conditions\FormConditionRule',
                        'uid' => $ruleUid,
                        'operator' => 'in',
                        'values' => $sectionIds,
                    ]),
                ];

                break;
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\UserActivatedNotificationEvent':
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\UserDeletedNotificationEvent':
            case 'BarrelStrength\Sprout\transactional\components\notificationevents\UserLoggedInNotificationEvent':
                $conditionClass = 'craft\elements\conditions\users\UserCondition';
                $conditionConfig = [
                    'elementType' => 'craft\elements\User',
                    'fieldContext' => 'global',
                ];
                break;
            default:
                // case 'BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent':
                // No Settings or Manual migration. Return no settings.
                return [];
                break;
        }

        if (!empty($sendRule) && $sendRule !== '*') {
            // Process Send Rule for ALL items
            $ruleUid = StringHelper::UUID();
            $conditionRules[] = [
                'uid' => $ruleUid,
                'class' => 'BarrelStrength\Sprout\transactional\components\conditions\TwigExpressionConditionRule',
                'type' => Json::encode([
                    'class' => 'BarrelStrength\Sprout\transactional\components\conditions\TwigExpressionConditionRule',
                    'uid' => $ruleUid,
                    'twigExpression' => $sendRule,
                ]),
                'operator' => '',
                'twigExpression' => $sendRule,
            ];
        }

        if (!$conditionRules) {
            return [];
        }

        return [
            'conditionRules' => [
                'class' => $conditionClass,
                'config' => Json::encode($conditionConfig),
                'conditionRules' => $conditionRules,
                'new-rule-type' => '',
            ],
        ];
    }
}
