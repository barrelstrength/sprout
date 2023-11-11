<?php

namespace BarrelStrength\Sprout\transactional\components\formfeatures;

use BarrelStrength\Sprout\core\components\fieldlayoutelements\RelationsTableField;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\DefineFormFeatureSettingsEvent;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\components\events\RegisterFormFeatureTabsEvent;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\models\FieldLayoutTab;
use yii\db\Expression;

class TransactionalFormFeature implements RelationsTableInterface
{
    public static function defineFormTypeSettings(DefineFormFeatureSettingsEvent $event): void
    {
        $event->featureSettings[self::class] = [
            'label' => Craft::t('sprout-module-transactional', 'Enable Notifications'),
        ];
    }

    public static function registerTransactionalTab(RegisterFormFeatureTabsEvent $event): void
    {
        $element = $event->element ?? null;

        if (!$element instanceof FormElement) {
            return;
        }

        $formType = $element->getFormType();
        $featureSettings = $formType->featureSettings[self::class] ?? [];
        $enableTab = $featureSettings['enabled'] ?? false;

        if (!$enableTab) {
            return;
        }

        $fieldLayout = $event->fieldLayout;

        Craft::$app->getView()->registerJs('new NotificationEventsRelationsTable(' . $element->id . ', ' . $element->siteId . ');');

        $notificationsTab = new FieldLayoutTab();
        $notificationsTab->layout = $fieldLayout;
        $notificationsTab->name = Craft::t('sprout-module-transactional', 'Notifications');
        $notificationsTab->uid = 'SPROUT-UID-FORMS-NOTIFICATIONS-TAB';
        $notificationsTab->sortOrder = 50;
        $notificationsTab->setElements([
            self::getRelationsTableField($element),
        ]);

        $event->tabs[] = $notificationsTab;

        // Insert tab before the Settings tab
        //$index = array_search('SPROUT-UID-FORMS-SETTINGS-TAB', array_column($event->tabs, 'uid'), true);
        //array_splice($event->tabs, $index, 0, [$notificationsTab]);
    }

    public static function getRelationsTableField(Element $element): RelationsTableField
    {
        $notificationEventRows = self::getTransactionalRelations($element);

        $options = EmailTypeHelper::getEmailTypesOptions();

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-transactional', 'New Email Type...'),
                'value' => '',
            ],
        ];

        foreach ($options as $option) {
            $optionValues[] = $option;
        }

        $createSelect = Cp::selectHtml([
            'id' => 'new-transactional-email',
            'name' => 'emailTypeUid',
            'options' => $optionValues,
            'value' => '',
        ]);

        $sidebarMessage = Craft::t('sprout-module-transactional', 'This page lists any transactional email that are known to be related to the events triggered by this form.');
        $sidebarHtml = Html::tag('div', Html::tag('p', $sidebarMessage), [
            'class' => 'meta read-only',
        ]);

        return new RelationsTableField([
            'attribute' => 'notification-event-relations',
            'rows' => $notificationEventRows,
            'newButtonHtml' => $createSelect,
            'sidebarHtml' => $sidebarHtml,
        ]);
    }

    public static function getTransactionalRelations(Element $element): array
    {
        $notificationEventTypes = TransactionalModule::getInstance()->notificationEvents->getNotificationEventRelationsTypes();

        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "eventId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "$.eventId")');
        }

        $query = TransactionalEmailElement::find()
            ->orderBy('sprout_emails.subjectLine')
            ->where(['in', $expression, $notificationEventTypes]);

        /** @var EmailElement[] $emails */
        $emails = $query->all();

        $submission = new SubmissionElement();
        $submission->formId = $element->id;

        $submissionEvent = new OnSaveSubmissionEvent();
        $submissionEvent->submission = $submission;

        $relatedEmails = [];

        // At this point, we're assuming all emails have Save Submission Events
        foreach ($emails as $email) {

            /** @var TransactionalEmailVariant $emailVariantSettings */
            $emailVariantSettings = $email->getEmailVariant();
            $notificationEvent = $emailVariantSettings->getNotificationEvent($email, $submissionEvent);

            // If we have no rules, all forms will match
            if (!$rules = $notificationEvent->conditionRules['conditionRules'] ?? null) {
                $relatedEmails[] = $email;
                continue;
            }

            foreach ($rules as $key => $rule) {
                if ($rule['class'] !== 'BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionFormConditionRule') {
                    unset($rules[$key]);
                }
            }

            // Just in case we have rules, but no rules are the SubmissionFormConditionRule, all forms will still match
            if (empty($rules)) {
                $relatedEmails[] = $email;
                continue;
            }

            // If we have a rule, we should now have a single FormConditionRule and can match against it
            // Assign the single rule back to the conditionRules attribute
            $notificationEvent->conditionRules['conditionRules'] = $rules;

            if (!$notificationEvent->matchNotificationEvent($submissionEvent)) {
                continue;
            }

            $relatedEmails[] = $email;
        }

        $rows = array_map(static function($element) {
            return [
                'elementId' => $element->id,
                'name' => $element->title,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element->getEmailType()::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $relatedEmails);

        return $rows;
    }
}
