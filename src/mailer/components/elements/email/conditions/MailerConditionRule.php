<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

class MailerConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Mailer (Sprout)');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['mailerUid'];
    }

    protected function options(): array
    {
        $mailers = MailerHelper::getMailers();

        return array_map(static function($mailer) {
            return [
                'label' => $mailer->name,
                'value' => $mailer->uid,
            ];
        }, $mailers);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        return in_array($element->mailerUid, $this->getValues(), true);
    }
}
