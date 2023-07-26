<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\TitleConditionRule;
use craft\elements\db\ElementQueryInterface;

class SubjectLineConditionRule extends TitleConditionRule
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Subject Line');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['subjectLine'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->subjectLine($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->subjectLine);
    }
}
