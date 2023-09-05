<?php

namespace BarrelStrength\Sprout\transactional\components\elements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailEmailVariant;
use Craft;
use craft\helpers\Json;
use yii\db\Expression;

class TransactionalEmailElementQuery extends EmailElementQuery
{
    public ?string $emailVariantType = TransactionalEmailEmailVariant::class;

    public ?array $notificationEventFilterRule = null;

    public function notificationEventFilterRule(array $value): static
    {
        $this->notificationEventFilterRule = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->subQuery->andWhere([
            'sprout_emails.emailVariantType' => TransactionalEmailEmailVariant::class,
        ]);

        if ($this->notificationEventFilterRule) {
            $this->applyNotificationEventFilter($this->notificationEventFilterRule);
        }

        return parent::beforePrepare();
    }

    public function applyNotificationEventFilter($params): void
    {
        $operator = $params['operator'] ?? null;
        $searchValues = $params['values'] ?? [];

        if (!$operator) {
            return;
        }

        if (Craft::$app->getDb()->getIsPgsql()) {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "eventId")');
        } else {
            $expression = new Expression('JSON_EXTRACT(sprout_emails.emailVariantSettings, "$.eventId")');
        }

        /**
         * MULTIPLE VALUES RENDER THIS QUERY
         * AND (
         *   JSON_EXTRACT(sprout_emails.emailVariantSettings, "$.eventId") IN (
         *     '\"BarrelStrength\\\\Sprout\\\\transactional\\\\components\\\\notificationevents\\\\EntryDeletedNotificationEvent\"',
         *     '\"BarrelStrength\\\\Sprout\\\\transactional\\\\components\\\\notificationevents\\\\EntryCreatedNotificationEvent\"'
         *   )
         * )
         *
         * SINGULAR VALUE RENDER THIS QUERY
         * AND (
         *   JSON_EXTRACT(sprout_emails.emailVariantSettings, "$.eventId")='BarrelStrength\\Sprout\\transactional\\components\\notificationevents\\EntrySavedNotificationEvent'
         * )
         */
        if (count($searchValues) > 1) {
            $searchValues = array_map(static function($value) {
                return Json::encode($value);
            }, $searchValues);
        }

        $this->subQuery->andWhere([
            $operator,
            $expression,
            $searchValues,
        ]);
    }
}
