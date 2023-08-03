<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventConditionRuleTrait;
use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\Entry;
use craft\helpers\ElementHelper;

class IsUpdatedEntryConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    use ElementEventConditionRuleTrait;

    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Is Updated Entry');
    }

    public function getExclusiveQueryParams(): array
    {
        return [
            '_canonicalId',
            'firstSave',
            'status',
            'draftId',
            'revisionId',
            'resaving',
            'propagating',
        ];
    }

    public function matchElement(ElementInterface $element): bool
    {
        $isUpdatedEntry =
            !$element->firstSave &&
            $element->getIsCanonical() &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            !ElementHelper::isDraftOrRevision($element) &&
            !$element->resaving &&
            !$element->propagating;

        return $this->matchValue($isUpdatedEntry);
    }
}
