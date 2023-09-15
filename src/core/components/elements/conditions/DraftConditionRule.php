<?php

namespace BarrelStrength\Sprout\core\components\elements\conditions;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

class
DraftConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-core', 'Is Draft');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['draftId'];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->draftId($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var ElementInterface $element */
        return $this->matchValue($element->getIsDraft());
    }
}
