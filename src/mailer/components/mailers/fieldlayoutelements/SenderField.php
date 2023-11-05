<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\App;

class SenderField extends BaseNativeField
{
    public bool $mandatory = true;

    public bool $required = true;

    public string $attribute = 'sender';

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof EmailElement) {
            throw new MissingComponentException('Email Element must exist before rendering edit page.');
        }

        $senderOptions = [];

        $mailer = $element->getMailer();
        $mailerInstructionsSettings = $element->getMailerInstructions();

        foreach ((array)$mailer->approvedSenders as $approvedSender) {
            if (!$approvedSender['fromEmail']) {
                continue;
            }

            $sender = App::parseEnv($approvedSender['fromName']) . ' <' . App::parseEnv($approvedSender['fromEmail']) . '>';

            $senderOptions[] = [
                'label' => $sender,
                'value' => $sender,
            ];
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/mailers/SystemMailer/sender.twig', [
            'element' => $element,
            'field' => $this,
            'selectedSenderOption' => $mailerInstructionsSettings->getSenderAsString(),
            'mailerInstructionsSettings' => $mailerInstructionsSettings,
            'senderOptions' => $senderOptions,
            'senderEditBehavior' => $mailer->senderEditBehavior,
            'mailer' => $mailer,
            'mailSettings' => App::mailSettings(),
        ]);
    }
}
