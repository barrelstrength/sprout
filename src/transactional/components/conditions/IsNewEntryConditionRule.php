<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\ElementHelper;

class IsNewEntryConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
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
            'status',
            'draftId',
            'revisionId',
            'resaving',
            'propagating',
        ];
    }

    protected function inputHtml(): string
    {
        // This rule always returns true, so no input is needed
        return '';
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
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

        $isNewEntryBecauseStatusChangedToLive =
            $element->enabled === true &&
            $element->isAttributeDirty('enabled') &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        return $isNewEntryForFirstTime || $isNewEntryBecauseStatusChangedToLive;
    }
}
