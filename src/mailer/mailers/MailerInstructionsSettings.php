<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\base\Model;

abstract class MailerInstructionsSettings extends Model implements MailerInstructionsInterface
{
    use MailerInstructionsTrait;
}
