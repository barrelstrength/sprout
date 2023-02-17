<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseField;

class SenderField extends BaseField
{
    public string $type = 'select';

    public bool $mandatory = true;

    public string $attribute = 'sender';

    public bool $required = true;

    public function attribute(): string
    {
        return $this->attribute;
    }

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'From');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $senderOptions = [];

        $mailer = $element->getMailer();
        $mailerInstructionsSettings = $element->getMailerInstructionsSettings();

        foreach ($mailer->approvedSenders as $approvedSender) {
            $sender = $approvedSender['fromName'] . ' <' . $approvedSender['fromEmail'] . '>';
            $senderOptions[] = [
                'label' => $sender,
                'value' => $sender,
            ];
        }

        $selectField = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'type' => $this->type,
            'describedBy' => $this->describedBy($element, $static),
            'name' => 'mailerInstructionsSettings[' . $this->attribute() . ']',
            'value' => $mailerInstructionsSettings->sender,
            'options' => $senderOptions,
        ]);

        return $selectField;
    }
}
