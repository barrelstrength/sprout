<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class EmailThemeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Email Theme');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['emailThemeUid'];
    }

    protected function options(): array
    {
        $themes = EmailThemeHelper::getEmailThemes();

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
        return in_array($element->emailThemeUid, $this->getValues(), true);
    }
}
