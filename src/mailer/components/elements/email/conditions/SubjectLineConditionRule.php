<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\TitleConditionRule;
use craft\elements\db\ElementQueryInterface;

class SubjectLineConditionRule extends TitleConditionRule
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Subject Line');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['subjectLine'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EmailElementQuery $query */
        $query->subjectLine($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var EmailElement $element */
        return $this->matchValue($element->subjectLine);
    }
}
