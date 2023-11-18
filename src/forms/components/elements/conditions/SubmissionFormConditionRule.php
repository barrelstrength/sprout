<?php

namespace BarrelStrength\Sprout\forms\components\elements\conditions;

use BarrelStrength\Sprout\forms\components\elements\db\SubmissionElementQuery;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class SubmissionFormConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Form');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['formId'];
    }

    protected function options(): array
    {
        $forms = FormsModule::getInstance()->forms->getAllForms();

        return array_map(static function($form) {
            return [
                'label' => $form->name,
                'value' => $form->id,
            ];
        }, $forms);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var SubmissionElementQuery $query */
        $query->formId($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var SubmissionElement $element */
        if (in_array($element->formId, $this->getValues(), false)) {
            return true;
        }

        return false;
    }
}
