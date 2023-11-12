<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\conditions;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElementQuery;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
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
        $types = DataStudioModule::getInstance()->dataSources->getDataSourceTypes();

        return TemplateHelper::optionsFromComponentTypes($types);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var DataSetElementQuery $query */
        $query->type($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var DataSetElement $element */
        return $this->matchValue($element->type);
    }
}
