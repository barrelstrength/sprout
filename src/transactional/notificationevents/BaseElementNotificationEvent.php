<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use Craft;
use craft\elements\conditions\ElementCondition;
use craft\helpers\Json;
use http\Exception\InvalidArgumentException;

abstract class BaseElementNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public ElementCondition|null $condition = null;

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'condition';

        return $attributes;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (isset($values['conditionRules'])) {
            $conditionRules = Json::decodeIfJson($values['conditionRules']);

            $condition = Craft::$app->conditions->createCondition($conditionRules);

            if (!$condition instanceof ElementCondition) {
                throw new InvalidArgumentException('Unable to assign ElementCondition attributes to a non-ElementCondition.');
            }

            $condition->elementType = static::getEventClassName();

            $this->condition = $condition;

            unset($values['conditionRules']);
        }
    }
}
