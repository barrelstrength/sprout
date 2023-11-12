<?php

namespace BarrelStrength\Sprout\redirects\components\elements\conditions;

use BarrelStrength\Sprout\redirects\components\elements\db\RedirectElementQuery;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\redirects\StatusCode;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class StatusCodeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-redirects', 'Status Code');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['statusCode'];
    }

    protected function options(): array
    {
        return [
            StatusCode::PERMANENT => Craft::t('sprout-module-redirects', '301 - Permanent'),
            StatusCode::TEMPORARY => Craft::t('sprout-module-redirects', '302 - Temporary'),
            StatusCode::PAGE_NOT_FOUND => Craft::t('sprout-module-redirects', '404 - Page Not Found'),
        ];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var RedirectElementQuery $query */
        $query->statusCode($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var RedirectElement $element */
        return $this->matchValue($element->statusCode);
    }
}
