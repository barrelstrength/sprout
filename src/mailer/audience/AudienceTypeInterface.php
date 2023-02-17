<?php

namespace BarrelStrength\Sprout\mailer\audience;

use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use craft\base\SavableComponentInterface;

interface AudienceTypeInterface extends SavableComponentInterface
{
    public function getHandle(): string;

    /**
     * @return MailingListRecipient[]
     */
    public function getRecipients(): array;
}
