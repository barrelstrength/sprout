<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use craft\helpers\UrlHelper;

trait EmailPreviewTrait
{
    public function getPreviewUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/email/preview/' . $this->id);
    }
}
