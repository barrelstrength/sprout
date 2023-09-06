<?php

namespace BarrelStrength\Sprout\mailer\components\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\AudienceField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\ReplyToField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\SenderField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\TestToEmailUiElement;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\ToField;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsInterface;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use Craft;
use craft\elements\Asset;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fs\Local;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use Exception;
use yii\base\ErrorException;
use yii\mail\MessageInterface;

abstract class SystemMailer extends Mailer implements MailerSendTestInterface
{
    public ?array $approvedSenders = null;

    public ?array $approvedReplyToEmails = null;

    protected array $_attachmentExternalFilePaths = [];

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): array
    {
        return [
            SenderField::class,
            ReplyToField::class,
            ToField::class,
            AudienceField::class,
        ];
    }

    public static function defineNativeElements(DefineFieldLayoutElementsEvent $event): array
    {
        return [
            new TestToEmailUiElement(),
        ];
    }

    public function createFieldLayout(): ?FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => static::class,
        ]);

        $mailerTab = new FieldLayoutTab();
        $mailerTab->layout = $fieldLayout;
        $mailerTab->name = Craft::t('sprout-module-mailer', 'Mailer');
        $mailerTab->sortOrder = 0;
        $mailerTab->uid = StringHelper::UUID();
        $mailerTab->setElements([
            new SenderField(),
            new ReplyToField(),
            new HorizontalRule(),
            new ToField(),
            new AudienceField(),
            new TestToEmailUiElement(),
        ]);

        $fieldLayout->setTabs([
            $mailerTab,
        ]);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSettingsHtml(): ?string
    {
        $html = Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/mailers/SystemMailer/settings.twig', [
            'settings' => $this,
        ]);

        return $html;
    }

    public function getSendTestModalHtml(EmailElement $email = null): string
    {
        $testToEmailAddress = Craft::$app->getConfig()->getGeneral()->testToEmailAddress;

        if ($testToEmailAddress) {
            $warningMessage = Craft::t('sprout-module-mailer', 'Test email found in general config. All messages will be sent to the testToEmailAddress: {email}', [
                'email' => $testToEmailAddress,
            ]);
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/mailers/SystemMailer/send-test-fields.twig', [
            'email' => $email,
            'warningMessage' => $warningMessage ?? '',
        ]);
    }

    public function send(EmailElement $email, MailerInstructionsInterface $mailerInstructionsSettings): void
    {
        // Get any variables defined by Email Variant to make available to building mailing list recipients
        $templateVariables = $mailerInstructionsSettings->getAdditionalTemplateVariables($email);
        $mailingList = $mailerInstructionsSettings->getMailingList($email, $templateVariables);

        $emailType = $email->getEmailType();
        $emailType->addTemplateVariables($templateVariables);
        $email->setEmailType($emailType);

        // Prepare the Message
        $message = new Message();

        $assets = $mailerInstructionsSettings->getMessageFileAttachments($email);
        $this->attachFilesToMessage($message, $assets);

        $sender = $mailerInstructionsSettings->getSender();

        // Make sure the current sender is in the list of approved senders
        if (!$this->isApprovedSender($sender)) {
            $email->addError('mailerInstructionsSettings', 'Sender is not in list of approved senders.');
        }

        $replyTo = $mailerInstructionsSettings->getReplyToEmail();

        // Make sure the current replyTo address is in the list of approved replyTos
        if (!$this->isApprovedReplyTo($replyTo, key($sender))) {
            $email->addError('mailerInstructionsSettings', 'Reply To address is not in list of approved addresses.');
        }

        $message->setFrom($sender);
        $message->setReplyTo($replyTo);

        // If we have errors before we start processing recipients, throw an error
        if ($email->hasErrors()) {
            throw new ErrorException('Email has errors');
        }

        foreach ($mailingList->getRecipients() as $recipient) {

            try {
                $message->setTo($recipient->getSender());

                $this->_buildMessage($message, $email, $recipient, $mailerInstructionsSettings);

                if ($email->hasErrors() || $recipient->hasErrors()) {
                    $mailingList->markAsFailed($recipient);
                    continue;
                }

                if ($message->send()) {
                    $mailingList->markAsProcessed($recipient);
                } else {
                    $mailingList->markAsFailed($recipient);
                    continue;
                }
            } catch (Exception $e) {
                $recipient->addError($e->getMessage());
                $mailingList->markAsFailed($recipient);
            }
        }

        $mailingList->validate();

        if ($mailingList->hasErrors()) {
            $email->addError('recipients', $mailingList->getErrors());
            Craft::error(sprintf(
                'Email recipient errors for Email ID %s: %s',
                $email->id,
                UrlHelper::cpUrl($email->getCpEditUrl())
            ));
            Craft::error($email->getErrors());
        }

        $this->deleteExternalFilePaths($this->_attachmentExternalFilePaths);
    }

    protected function isApprovedSender(array $sender): bool
    {
        $approvedSenders = [];

        array_walk($this->approvedSenders,
            static function($approvedSender) use (&$approvedSenders) {
                $approvedSenders[App::parseEnv($approvedSender['fromEmail'])] = App::parseEnv($approvedSender['fromName']);
            });

        foreach ($approvedSenders as $approvedSenderEmail => $approvedSenderName) {
            if (isset($sender[$approvedSenderEmail]) && $sender[$approvedSenderEmail] === $approvedSenderName) {
                return true;
            }
        }

        return false;
    }

    protected function isApprovedReplyTo(mixed $replyTo, string $sender = null): bool
    {
        if (is_array($replyTo)) {
            $replyTo = key($replyTo);
        }

        $approvedReplyToEmails = array_map(static fn($email) => $email['replyToEmail'], $this->approvedReplyToEmails);
        $approvedReplyToEmails = array_filter(array_merge($approvedReplyToEmails, [$sender]));

        return in_array($replyTo, $approvedReplyToEmails, true);
    }

    protected function _buildMessage(
        MessageInterface            $message,
        EmailElement                $email,
        MailingListRecipient        $recipient,
        MailerInstructionsInterface $mailerInstructionsSettings,
    ): void {

        $view = Craft::$app->getView();
        $emailType = $email->getEmailType();
        $emailType->addTemplateVariable('recipient', $recipient);
        $templateVariables = $emailType->getTemplateVariables();

        $subjectLine = $mailerInstructionsSettings->getSubjectLine($email);
        $email->subjectLine = $view->renderObjectTemplate($subjectLine, $templateVariables);
        $email->preheaderText = $view->renderObjectTemplate($email->preheaderText, $templateVariables);
        $email->defaultMessage = $view->renderObjectTemplate($email->defaultMessage, $templateVariables);

        $emailType->addTemplateVariable('email', $email);

        $textBody = trim($emailType->getTextBody());
        $htmlBody = trim($emailType->getHtmlBody());

        if (empty($textBody)) {
            throw new \yii\base\Exception('Text template is blank.');
        }

        if (empty($htmlBody)) {
            throw new \yii\base\Exception('HTML template is blank.');
        }

        $message->setSubject($email->subjectLine);
        $message->setTextBody($textBody);
        $message->setHtmlBody($htmlBody);
    }

    /**
     * @param Asset[] $assets
     */
    protected function attachFilesToMessage(MessageInterface $message, array $assets): void
    {
        foreach ($assets as $asset) {

            $name = $asset->getFilename();
            $volume = $asset->getVolume()->getFs();

            if ($volume instanceof Local) {
                $path = $this->getLocalAssetFilePath($asset);
            } else {
                // External Asset sources
                $path = $asset->getCopyOfFile();
                // let's save the path to delete it after sent
                $this->_attachmentExternalFilePaths[] = $path;
            }

            if ($path) {
                $message->attach($path, ['fileName' => $name]);
            }
        }
    }

    protected function getLocalAssetFilePath(Asset $asset): string
    {
        /**
         * @var $volume Local
         */
        $volume = $asset->getVolume();

        $path = $volume->getRootPath() . DIRECTORY_SEPARATOR . $asset->getPath();

        return FileHelper::normalizePath($path);
    }

    protected function deleteExternalFilePaths($externalFilePaths): void
    {
        foreach ($externalFilePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['approvedSenders'], 'approvedSenderEmailsAreUnique'];
        $rules[] = [['approvedReplyToEmails'], 'replyToEmailsAreUnique'];

        return $rules;
    }

    public function approvedSenderEmailsAreUnique(): void
    {
        $emailWithCount = [];

        foreach ($this->approvedSenders as $approvedSender) {
            if (isset($emailWithCount[$approvedSender['fromEmail']])) {
                $emailWithCount[$approvedSender['fromEmail']]++;
            } else {
                $emailWithCount[$approvedSender['fromEmail']] = 1;
            }
        }

        foreach ($emailWithCount as $email => $count) {
            if ($count > 1) {
                $this->addError('approvedSenders', 'Sender email addresses must be unique.');
            }
        }
    }

    public function replyToEmailsAreUnique(): void
    {
        $emailWithCount = [];

        foreach ($this->approvedReplyToEmails as $replyToEmail) {
            if (isset($emailWithCount[$replyToEmail['replyToEmail']])) {
                $emailWithCount[$replyToEmail['replyToEmail']]++;
            } else {
                $emailWithCount[$replyToEmail['replyToEmail']] = 1;
            }
        }

        foreach ($emailWithCount as $email => $count) {
            if ($count > 1) {
                $this->addError('approvedReplyToEmails', 'Reply To email addresses must be unique.');
            }
        }
    }
}
