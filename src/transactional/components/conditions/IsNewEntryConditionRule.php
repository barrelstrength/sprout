<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventConditionRuleTrait;
use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\Entry;
use craft\helpers\ElementHelper;

class IsNewEntryConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
    use ElementEventConditionRuleTrait;

    public bool $value = true;

    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Is New Live Entry');
    }

    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    public function getExclusiveQueryParams(): array
    {
        return [
            '_canonicalId',
            'firstSave',
            'draftId',
            'revisionId',
            'resaving',
            'propagating',

            // Including 'status' makes this not display on the main Element Index
            'status',
        ];
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
    }

    public function matchElement(ElementInterface $element): bool
    {
        $isNewEntryForFirstTime =
            $element->firstSave &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        $isNewEntryBecauseStatusChangedToLive =
            $element->enabled === true &&
            $element->isAttributeDirty('enabled') &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        return $isNewEntryForFirstTime || $isNewEntryBecauseStatusChangedToLive;
    }
}
