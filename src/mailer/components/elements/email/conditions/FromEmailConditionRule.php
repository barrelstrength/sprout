<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class FromEmailConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'From Email');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['email'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EmailElementQuery $query */
        $query->fromEmail($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var EmailElement $element */
        return $this->matchValue($element->fromEmail);
    }
}
