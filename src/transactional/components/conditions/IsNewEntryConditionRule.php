<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\ElementHelper;

class IsNewEntryConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Is New Live Entry');
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

    public function modifyQuery(ElementQueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        $isNewEntryForFirstTime =
            $element->firstSave &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        if ($isNewEntryForFirstTime) {
            return true;
        }

        // OR!!!

        // When not the firstSave but
        // Status is changed to Live...

        $isNewEntryBecauseStatusChangedToLive = false;

        return $isNewEntryBecauseStatusChangedToLive;
    }
}
