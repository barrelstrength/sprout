<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\BaseNativeField;

class ReplyToField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'replyToEmail';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Reply-to');
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
}
