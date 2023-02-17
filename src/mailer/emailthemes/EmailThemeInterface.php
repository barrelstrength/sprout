<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use craft\models\FieldLayout;

interface EmailThemeInterface
{
    public function name(): ?string;

    public function isEditable(): bool;

    public function htmlEmailTemplatePath(): ?string;

    public function copyPasteEmailTemplatePath(): ?string;

    public function getTemplateMode(): string;

    public function getFieldLayout(): FieldLayout;
}
