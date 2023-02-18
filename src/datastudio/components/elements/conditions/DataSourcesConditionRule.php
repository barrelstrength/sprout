<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\conditions;

use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

class DataSourcesConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('sprout-module-data-studio', 'Data Sources');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['type'];
    }

    protected function options(): array
    {
        return DataStudioModule::getInstance()->dataSources->getDataSourceOptions();
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->type($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->type);
    }
}
