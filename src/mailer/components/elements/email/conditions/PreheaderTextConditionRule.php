<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\conditions\BaseLightswitchConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class PreheaderTextConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Preheader Text');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['preheaderText'];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No query changes. For Email Element Editor UI condition only.
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var EmailElement $element */
        $emailType = $element->getEmailType();

        return $this->matchValue($emailType->displayPreheaderText);
    }
}
