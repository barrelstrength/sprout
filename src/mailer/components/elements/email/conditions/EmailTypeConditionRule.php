<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class EmailTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Email Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['emailTypeUid'];
    }

    protected function options(): array
    {
        return EmailTypeHelper::getEmailTypesOptions();
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        return in_array($element->emailTypeUid, $this->getValues(), true);
    }
}
