<?php

namespace BarrelStrength\Sprout\transactional\components\elements\conditions;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use yii\db\Expression;
use yii\db\QueryInterface;

class NotificationEventConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Notification Event');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['emailTypeSettings'];
    }

    protected function options(): array
    {
        $events = TransactionalModule::getInstance()->notificationEvents->getNotificationEvents();

        return TemplateHelper::optionsFromComponentTypes($events);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailTypeSettings, "eventId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailTypeSettings, "$.eventId")');

        }

        $operator = $this->operator === 'in' ? 'in' : 'not in';

        // NOTE: MySQL syntax of multiple selected items is different than a single item
        // and in one case we need to encode the values to match properly in the query
        if (count($this->getValues()) > 1) {
            $searchValues = array_map(static function($value) {
                return Json::encode($value);
            }, $this->getValues());

            //AND (
            //JSON_EXTRACT(sprout_emails.emailTypeSettings, "$.eventId") IN (
            //    '\"BarrelStrength\\\\Sprout\\\\transactional\\\\components\\\\notificationevents\\\\EntryDeletedNotificationEvent\"',
            //    '\"BarrelStrength\\\\Sprout\\\\transactional\\\\components\\\\notificationevents\\\\EntrySavedNotificationEvent\"'
            //)
            //)
            $query->andWhere([
                $operator,
                $expression,
                $searchValues
            ]);
        } else {
            //AND (
            //JSON_EXTRACT(sprout_emails.emailTypeSettings, "$.eventId")='BarrelStrength\\Sprout\\transactional\\components\\notificationevents\\EntrySavedNotificationEvent'
            //)
            $query->andWhere([
                $operator,
                $expression,
                $this->getValues()
            ]);
        }


        //\Craft::dd($searchValues);
        //$query->andWhere(['or', ['in', $expression, $class]);

        //
        //\Craft::dd($query->getRawSql());

        //JSON_CONTAINS(
        #            sprout_emails.emailTypeSettings,
        #            JSON_QUOTE("BarrelStrength\\Sprout\\transactional\\components\\notificationevents\\EntrySavedNotificationEvent"),
        #            '$.eventId'
        #            ) AS 'settings3'

        //foreach ($this->getValues() as $class) {
        //    $expression = new Expression('JSON_EXTRACT(sprout_emails.emailTypeSettings, "$.eventId")');
            //\Craft::dd(trim(Json::encode(trim(Json::encode($class), '"')), '"'));
            //$query->andWhere(['=', $expression, Json::encode($class)]);
            //$query->orWhere([
            //    'JSON_CONTAINS',
            //    'sprout_emails.emailTypeSettings',
            //    Json::encode($class),
            //    '$.eventId'
            //]);
        //}

        //$searchValues = array_map(static function($value) {
        //    return trim(Json::encode($value), '"');
        //    return trim(str_replace('\\','\\\\', Json::encode($value)), '"');
        //}, $this->getValues());
        //\Craft::dd($searchValues);
        //\Craft::dd($this->getValues());
        /** @var ElementQueryInterface $query */
        //$query->andWhere([
        //    'in',
        //    $expression,
        //    $this->getValues()
        //]);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var TransactionalEmailElement $element */
        $emailTypeSettings = $element->getEmailTypeSettings();
        $notificationEvent = $emailTypeSettings->getNotificationEvent($element);

        /** @var ElementInterface $element */
        return $this->matchValue($notificationEvent::class);
    }
}
