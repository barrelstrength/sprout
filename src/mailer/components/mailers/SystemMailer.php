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

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

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
            'mailer' => $this,
            'warningMessage' => $warningMessage ?? '',
        ]);
    }

    public function send(EmailElement $email, MailerInstructionsInterface $mailerInstructionsSettings): void
    {
        // Get any variables defined by Email Type to make available to building mailing list recipients
        $templateVariables = $mailerInstructionsSettings->getAdditionalTemplateVariables($email);
        $mailingList = $mailerInstructionsSettings->getMailingList($email, $templateVariables);

        $emailTheme = $email->getEmailTheme();
        $emailTheme->addTemplateVariables($templateVariables);
        $email->setEmailTheme($emailTheme);

        // Prepare the Message
        $message = new Message();

        $assets = $mailerInstructionsSettings->getMessageFileAttachments($email);
        $this->attachFilesToMessage($message, $assets);

        $message->setFrom($mailerInstructionsSettings->getSender());
        $message->setReplyTo($mailerInstructionsSettings->getReplyToEmail());

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

    protected function _buildMessage(
        MessageInterface            $message,
        EmailElement                $email,
        MailingListRecipient        $recipient,
        MailerInstructionsInterface $mailerInstructionsSettings,
    ): void {

        $view = Craft::$app->getView();
        $emailTheme = $email->getEmailTheme();
        $emailTheme->addTemplateVariable('recipient', $recipient);
        $templateVariables = $emailTheme->getTemplateVariables();

        $subjectLine = $mailerInstructionsSettings->getSubjectLine($email);
        $email->subjectLine = $view->renderObjectTemplate($subjectLine, $templateVariables);
        $email->preheaderText = $view->renderObjectTemplate($email->preheaderText, $templateVariables);
        $email->defaultMessage = $view->renderObjectTemplate($email->defaultMessage, $templateVariables);

        $emailTheme->addTemplateVariable('email', $email);

        $textBody = trim($emailTheme->getTextBody());
        $htmlBody = trim($emailTheme->getHtmlBody());

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
}
