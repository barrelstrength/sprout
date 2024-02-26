<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;

class m240225_000000_remove_default_message_max_length extends Migration
{
    public function safeUp(): void
    {
        $config = Craft::$app->getProjectConfig()->get('sprout.sprout-module-mailer.emailTypes') ?? [];
        $config = ProjectConfig::unpackAssociativeArray($config);

        foreach ($config as $emailType) {
            foreach ($emailType['fieldLayouts'] as $fieldLayout) {
                foreach ($fieldLayout['tabs'] as $tab) {
                    foreach ($tab['elements'] as $element) {
                        if ($element['type'] === 'BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields\DefaultMessageField') {
                            $element['maxlength'] = null;
                        }
                    }
                }
            }
        }

        $config = ProjectConfig::packAssociativeArray($config);
        Craft::$app->getProjectConfig()->set('sprout.sprout-module-mailer.emailTypes', $config);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
