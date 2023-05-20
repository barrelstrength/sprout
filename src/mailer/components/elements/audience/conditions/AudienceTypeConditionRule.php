<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience\conditions;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

class AudienceTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Audience Types');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['audienceType'];
    }

    protected function options(): array
    {
        $audienceTypes = MailerModule::getInstance()->audiences->getAudienceTypeInstances();

        return TemplateHelper::optionsFromComponentTypes($audienceTypes);
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
