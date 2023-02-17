<?php

namespace BarrelStrength\Sprout\forms\components\emailtemplates;

use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;

class DefaultFormSummaryEmailTemplates extends EmailTheme
{
    public function getName(): string
    {
        return Craft::t('sprout-module-forms', 'Form Summary (Sprout)');
    }

    public function getTemplateRoot(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot');
    }

    public function getPath(): string
    {
        return 'emailtemplates/submission';
    }
}



