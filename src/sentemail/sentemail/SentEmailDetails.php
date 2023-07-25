<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use craft\base\Model;
use craft\helpers\Json;
use yii\helpers\Inflector;

class SentEmailDetails extends Model
{
    // Delivery Info

    /**
     * The status of the email that was sent
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

    public ?string $transportSettings = null;

    public function getTransportSettingsAsArray(): array
    {
        $settingsAttributes = Json::decodeIfJson($this->transportSettings);

        if (is_array($settingsAttributes)) {
            foreach ($settingsAttributes as $name => $value) {
                $transportSettings[Inflector::camel2words($name, true)] = $value;
            }
        }

        return $transportSettings ?? [];
    }
}
