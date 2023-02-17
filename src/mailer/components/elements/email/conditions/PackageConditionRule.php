<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\conditions\BaseSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class PackageConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Package Type');
    }

    public function getExclusiveQueryParams(): array
    {
        $emailTypes = MailerModule::getInstance()->emailTypes->getRegisteredEmailTypes();

        return $emailTypes;
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EmailElementQuery $query */
        $query->emailType($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var EmailElement $element */
        $emailType = $element->getEmailTypeSettings();

        return get_class($emailType) === $this->value;
    }

    protected function options(): array
    {
        $emailTypes = MailerModule::getInstance()->emailTypes->getRegisteredEmailTypes();

        return TemplateHelper::optionsFromComponentTypes($emailTypes);
    }
}
