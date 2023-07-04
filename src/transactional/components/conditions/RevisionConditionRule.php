<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

class RevisionConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Is Revision');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['revisionId'];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->revisionId($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var ElementInterface $element */
        return $this->matchValue($element->getIsRevision());
    }
}
