<?php

namespace BarrelStrength\Sprout\forms\components\elements\conditions;

use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class FormTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Form Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['formTypeUid'];
    }

    protected function options(): array
    {
        $formTypes = FormTypeHelper::getFormTypes();

        return array_map(static function($formType) {
            return [
                'label' => $formType->name,
                'value' => $formType->uid,
            ];
        }, $formTypes);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        return in_array($element->formTypeUid, $this->getValues(), true);
    }
}
