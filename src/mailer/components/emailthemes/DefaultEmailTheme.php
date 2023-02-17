<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\core\modules\SettingsRecord;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;

class DefaultEmailTheme extends EmailTheme
{
    public ?string $handle = 'default';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Default Theme');
    }

    public function init(): void
    {
        $this->populateFieldLayoutId();

        parent::init();
    }

    public function isEditable(): bool
    {
        return false;
    }

    public function getTemplateRoot(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot');
    }

    public function htmlEmailTemplatePath(): string
    {
        return 'emailtemplates/default/email.twig';
    }

    private function populateFieldLayoutId(): void
    {
        $fieldLayoutId = SettingsRecord::find()
            ->select('settings')
            ->where([
                'moduleId' => 'sprout-module-mailer',
                'name' => 'defaultEmailTheme.fieldLayoutId',
            ])
            ->scalar();

        $this->fieldLayoutId = $fieldLayoutId;
    }
}



