<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use craft\base\Model;

class SentEmailDetails extends Model
{
    // Delivery Info

    /**
     * The status of the email that was sent
     *
     * @var string|null Sent, Error
     */
    public ?string $deliveryStatus = null;

    /**
     * Any response or error message generated while sending or attempting to send the email
     */
    public string $message = '';

    // Sender Info

    public ?string $senderName = null;

    public ?string $senderEmail = null;

    public ?string $craftVersion = null;

    public ?string $ipAddress = null;

    public ?string $userAgent = null;

    // Email Settings

    public ?string $transportType = null;

    public ?string $protocol = null;

    public ?string $host = null;

    public ?string $port = null;

    public ?string $username = null;

    public ?string $encryptionMethod = null;

    public ?string $timeout = null;
}
