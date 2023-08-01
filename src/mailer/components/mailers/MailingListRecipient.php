<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use craft\base\Model;
use Exception;
use Symfony\Component\Mime\Address;
use Craft;

class MailingListRecipient extends Model
{
    /**
     * The name of an email recipient
     */
    public ?string $name = null;

    /**
     * The email address of an email recipient
     */
    public ?string $email = null;

    /**
     * A string that will be parsed as an object template
     * before being added as $this->email
     *
     * i.e. {email} or {{ object.email }}
     */
    public ?string $emailTemplateString = null;

    private array $_customAttributes = [];

    public function __get($name)
    {
        if (array_key_exists($name, $this->_customAttributes)) {
            return $this->_customAttributes[$name];
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (Exception) {
            $this->_customAttributes[$name] = $value;
        }
    }

    public function isDynamicEmail(): bool
    {
        return $this->emailTemplateString !== null;
    }

    /**
     * Returns the From Address sender value
     */
    public function getSender(): string|array
    {
        if ($this->name) {
            return [$this->email => $this->name];
        }

        return $this->email;
    }

    /**
     * Recipients provides a comma-delimited list of recipients.
     *
     * Accepted values:
     * - Email: sprout@barrelstrengthdesign.com
     * - Name & Email: Sprout <sprout@barrelstrengthdesign.com>
     *
     * @return MailingListRecipient[]
     */
    public static function stringToMailingListRecipientList(string $recipients): array
    {
        $recipientsArray = array_map('trim', explode(',', $recipients));

        $recipients = array_map(static function($recipient) {

            $mailingListRecipient = new MailingListRecipient();

            try {
                if (str_contains($recipient, '{')) {
                    $mailingListRecipient->emailTemplateString = $recipient;
                } else {
                    $address = Address::create($recipient);
                    $mailingListRecipient->name = $address->getName();
                    $mailingListRecipient->email = $address->getAddress();
                }
            } catch (Exception $e) {
                $mailingListRecipient->addError('recipient', $e->getMessage());
            }

            return $mailingListRecipient;
        }, $recipientsArray);

        return $recipients;
    }
}
