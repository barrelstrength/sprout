<?php

namespace BarrelStrength\Sprout\mailer\emailvariants;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use craft\base\SavableComponent;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

/**
 * @mixin FieldLayoutBehavior
 *
 * @property array $additionalTemplateVariables
 */
abstract class EmailVariant extends SavableComponent
{
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
     * The short name that will be used as an identifier and URL slug for this Email Variant
     */
    abstract public static function refHandle(): ?string;

    /**
     * Returns the Mailer this Email Variant uses when sending email
     */
    abstract public function getMailer(EmailElement $email): ?Mailer;

    abstract public static function getDefaultMailer(): ?Mailer;

    /**
     * Returns the Element Class being used as the Element Index UI layer for this Email Variant
     */
    abstract public static function elementType(): string;

    /**
     * Returns the [[FieldLayoutTab]] model to display for this Email Variant
     * These values will be stored in [[sprout_emails.emailVariantSettings]]
     */
    public static function getFieldLayoutTab(FieldLayout $fieldLayout): ?FieldLayoutTab
    {
        return null;
    }

    /**
     * Returns any additional buttons desired for this Email Variant on the Email editor page
     */
    public static function getAdditionalButtonsHtml(EmailElement $email): string
    {
        return '';
    }

    /**
     * @see `EmailVariant::$_additionalTemplateVariables`
     */
    public function getAdditionalTemplateVariables(): mixed
    {
        return $this->_additionalTemplateVariables;
    }

    /**
     * @see `EmailVariant::$_additionalTemplateVariables`
     */
    public function addAdditionalTemplateVariables(mixed $variables): void
    {
        $this->_additionalTemplateVariables = $variables;
    }

    /**
     * Email variants can enable file attachments. Any specific logic to do so must be handled by the variant.
     *
     * If disabled, files will still be stored in Craft after form submission.
     * This only determines if they should also be attached and sent via email.
     */
    public bool $enableFileAttachments = false;

    /**
     * Show or hide the Element Editor Status Enabled setting for this Email Variant
     */
    public function canBeDisabled(): bool
    {
        return true;
    }

    /**
     * Set to true if this Email Variant needs to define custom EmailVariant::getStatusCondition() rules
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
