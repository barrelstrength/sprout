<?php

namespace BarrelStrength\Sprout\mailer\email;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use craft\base\SavableComponent;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;

/**
 * @mixin FieldLayoutBehavior
 *
 * @property array $additionalTemplateVariables
 */
abstract class EmailType extends SavableComponent
{
    /**
     * The short name that will be used as an identifier and URL slug for this Email Type
     */
    public ?string $handle = null;

    /**
     * Returns an array of data that will be provided to the template
     * as template variables. i.e. {{ object.title }}
     *
     * return [
     *   'email' => Provided by Sprout
     *   'recipient' => Provided by Sprout,
     *   'object' => Defined by this method. Can be object, array, string, etc.
     * ]
     */
    protected array $_additionalTemplateVariables = [];

    /**
     * Returns the Element Class being used as the Element Index UI layer for this Email Type
     */
    abstract public static function getElementIndexType(): string;

    /**
     * Returns an array of [[FieldLayoutTab]] models to display for this Email Type
     * These values will be stored in [[sprout_emails.emailTypeSettings]]
     */
    public static function getTabs(FieldLayout $fieldLayout): array
    {
        return [];
    }

    /**
     * Returns any additional buttons desired for this Email Type on the Email editor page
     */
    public static function getAdditionalButtonsHtml(EmailElement $email): string
    {
        return '';
    }

    /**
     * @see `EmailType::$_additionalTemplateVariables`
     */
    public function getAdditionalTemplateVariables(): mixed
    {
        return $this->_additionalTemplateVariables;
    }

    /**
     * @see `EmailType::$_additionalTemplateVariables`
     */
    public function addAdditionalTemplateVariables(string $name, mixed $value): void
    {
        $this->_additionalTemplateVariables[$name] = $value;
    }

    /**
     * Show or hide the Element Editor Status Enabled setting for this Email Type
     */
    public function canBeDisabled(): bool
    {
        return true;
    }

    /**
     * Set to true if this Email Type needs to define custom EmailType::getStatusCondition() rules
     */
    public function hasCustomStatuses(): bool
    {
        return false;
    }

    /**
     * @see [[ElementQuery::statusCondition()]]
     */
    public function getStatusCondition(string $status): mixed
    {
        return false;
    }
}
