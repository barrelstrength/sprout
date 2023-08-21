<?php

namespace BarrelStrength\Sprout\forms\components\elements\conditions;

use BarrelStrength\Sprout\forms\formthemes\FormThemeHelper;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class FormThemeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Form Theme');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['formThemeUid'];
    }

    protected function options(): array
    {
        $themes = FormThemeHelper::getFormThemes();

        return array_map(static function($theme) {
            return [
                'label' => $theme->name,
                'value' => $theme->uid,
            ];
        }, $themes);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        return in_array($element->formThemeUid, $this->getValues(), true);
    }
}
