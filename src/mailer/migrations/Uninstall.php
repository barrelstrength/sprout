<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\db\SproutTable;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = MailerModule::projectConfigPath();
        $coreModuleSettingsKey = MailerModule::projectConfigPath('modules.' . MailerModule::class);

        $this->delete(Table::ELEMENTS, ['type' => EmailElement::class]);

        // Order matters
        $this->dropTableIfExists(SproutTable::SUBSCRIPTIONS);
        $this->dropTableIfExists(SproutTable::AUDIENCES);
        $this->dropTableIfExists(SproutTable::EMAILS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);
    }
}
