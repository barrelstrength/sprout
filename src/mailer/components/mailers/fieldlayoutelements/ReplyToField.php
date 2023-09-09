<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\App;

class ReplyToField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'replyToEmail';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Reply-To');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof EmailElement) {
            throw new MissingComponentException('Email Element must exist before rendering edit page.');
        }

        $replyToOption[] = [
            'label' => Craft::t('sprout-module-mailer', 'Same as Sender'),
            'value' => '',
        ];

        $mailer = $element->getMailer();

        $mailerInstructionsSettings = $element->getMailerInstructions();

        $mailSettings =  App::mailSettings();

        if ($mailer->senderEditBehavior === SystemMailer::SENDER_BEHAVIOR_CRAFT) {

            $this->tip = Craft::t('sprout-module-mailer', 'Reply-To is set in the Craft Email Settings.');

            return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
                'name' => 'mailerInstructionsSettings[' . $this->attribute() . ']',
                'type' => 'email',
                'value' => $mailSettings->replyToEmail,
                'disabled' => true,
                'placeholder' => $mailSettings->fromEmail,
            ]);
        }

        if ($mailer->senderEditBehavior === SystemMailer::SENDER_BEHAVIOR_CURATED) {
            foreach ((array)$mailer->approvedReplyToEmails as $approvedReplyToEmail) {
                if (!$approvedReplyToEmail['replyToEmail']) {
                    continue;
                }

                $replyToOption[] = [
                    'label' => $approvedReplyToEmail['replyToEmail'],
                    'value' => $approvedReplyToEmail['replyToEmail'],
                ];
            }

            $selectField = Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
                'describedBy' => $this->describedBy($element, $static),
                'name' => 'mailerInstructionsSettings[' . $this->attribute() . ']',
                'value' => $mailerInstructionsSettings->replyToEmail,
                'options' => $replyToOption,
            ]);

            return $selectField;
        }

        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'name' => 'mailerInstructionsSettings[' . $this->attribute() . ']',
            'type' => 'email',
            'value' => $mailerInstructionsSettings->replyToEmail,
            'placeholder' => $mailerInstructionsSettings->sender['fromEmail'] ?? null,
        ]);
    }

        return $selectField;
    }
}
