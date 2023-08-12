<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;

class MailerSchemaHelper
{
    public const SPROUT_KEY = 'sprout';

    public static function insertDefaultMailerSettings(): void
    {
        $mailSettings = App::mailSettings();

        $mailer = new TransactionalMailer();
        $mailer->name = 'Transactional Mailer';
        $mailer->uid = StringHelper::UUID();
        $mailer->settings = [
            'approvedSenders' => [
                [
                    'fromName' => $mailSettings->fromName,
                    'fromEmail' => $mailSettings->fromEmail,
                ]
            ],
            'approvedReplyToEmails' => [
                [
                    'replyToEmail' => $mailSettings->replyToEmail
                ]
            ],
        ];

        MailerHelper::saveMailers([
            $mailer->uid => $mailer
        ]);
    }

    public static function createDefaultEmailThemeFieldLayout(): void
    {
        $emailTheme = new EmailMessageTheme();
        $emailTheme->name = 'Simple Message';
        $emailTheme->uid = StringHelper::UUID();

        EmailThemeHelper::saveEmailThemes([
            $emailTheme->uid => $emailTheme
        ]);
    }
}
