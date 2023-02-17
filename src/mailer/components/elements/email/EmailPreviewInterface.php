<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

interface EmailPreviewInterface
{
    public const EMAIL_TEMPLATE_TYPE_DYNAMIC = 'dynamic';

    public const EMAIL_TEMPLATE_TYPE_STATIC = 'static';

    /**
     * Dynamic or static
     */
    public function getPreviewType(): string;

    public function getPreviewUrl(): ?string;
}
