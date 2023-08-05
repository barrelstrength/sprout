<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\helpers\Html;
use craft\helpers\Json;

class UserDeletedNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is deleted');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is deleted.');
    }

    public static function conditionType(): string
    {
        return UserCondition::class;
    }

    public static function elementType(): string
    {
        return User::class;
    }

    public static function getEventClassName(): ?string
    {
        return User::class;
    }

    public static function getEventName(): ?string
    {
        return User::EVENT_AFTER_DELETE;
    }

    public function getTipHtml(): ?string
    {
        $html = Html::tag('p', Craft::t('sprout-module-transactional', 'Access the User Element in your email templates using the <code>object</code> variable. Example:'));
        $html .= Html::tag('p', Html::tag('em', Craft::t('sprout-module-transactional', 'This email was sent to: <code>{{ object.email }}</code>')));

        return $html;
    }

    public function getEventVariables(): mixed
    {
        return [
            'user' => $this?->event?->sender,
        ];
    }

    public function getMockEventVariables(): mixed
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($this->conditionRules) {
            $conditionRules = Json::decodeIfJson($this->conditionRules);
            $condition = Craft::$app->conditions->createCondition($conditionRules);
            $condition->elementType = User::class;

            $query = $condition->elementType::find();
            $condition->modifyQuery($query);

            $user = $query->one();
        }

        return [
            'user' => $user,
        ];
    }
}
