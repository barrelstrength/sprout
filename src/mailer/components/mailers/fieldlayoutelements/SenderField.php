<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
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
        if (!$element instanceof EmailElement) {
            throw new MissingComponentException('Email Element must exist before rendering edit page.');
        }

        $senderOptions = [];

        $mailer = $element->getMailer();
        $mailerInstructionsSettings = $element->getMailerInstructionsSettings();

        foreach ((array)$mailer->approvedSenders as $approvedSender) {
            if (!$approvedSender['fromEmail']) {
                continue;
            }

            $sender = $approvedSender['fromName'] . ' <' . $approvedSender['fromEmail'] . '>';
            $senderOptions[] = [
                'label' => $sender,
                'value' => $sender,
            ];
        }

        if (!$senderOptions) {
            $this->warning = Craft::t('sprout-module-mailer', 'Approved Senders must be added in email settings');
        }

        $selectField = Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
            'type' => $this->type,
            'describedBy' => $this->describedBy($element, $static),
            'name' => 'mailerInstructionsSettings[' . $this->attribute() . ']',
            'value' => $mailerInstructionsSettings->sender,
            'options' => $senderOptions,
        ]);

        return $selectField;
    }
}
