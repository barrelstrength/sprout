<?php

namespace BarrelStrength\Sprout\mailer\audience;

use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use craft\base\SavableComponentInterface;

interface AudienceTypeInterface extends SavableComponentInterface
{
    /**
     * @return MailingListRecipient[]
     */
    public function getRecipients(): array;

    /**
     * Returns the Element Index table attribute column HTML with the
     * link to the location where this Audience Type manages subscribers
     */
    public function getColumnAttributeHtml(): string;
}
