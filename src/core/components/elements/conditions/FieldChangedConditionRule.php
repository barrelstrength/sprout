<?php

namespace BarrelStrength\Sprout\core\components\elements\conditions;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventConditionRuleTrait;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;

class
FieldChangedConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    use ElementEventConditionRuleTrait;

    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'Field Changed');
    }

    public function getExclusiveQueryParams(): array
    {
        return $this->getValues();
    }

    protected function options(): array
    {
        $fields = Craft::$app->getFields()->getAllFields('global');

        return array_map(static function($field) {
            return [
                'label' => $field->name,
                'value' => $field->handle,
            ];
        }, $fields);
    }

    public function matchElement(ElementInterface $element): bool
    {
        foreach ($this->getValues() as $fieldHandle) {
            if ($element->isFieldDirty($fieldHandle)) {
                return true;
            }
        }

        return false;
    }
}
