<?php

namespace BarrelStrength\Sprout\forms\submissions;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use DateTime;

class SubmissionStatus extends Model
{
    public const SPAM_STATUS_HANDLE = 'spam';

    public ?int $id = null;

    public string $name = '';

    public string $handle = '';

    public string $color = SubmissionStatusColor::BLUE;

    public ?int $sortOrder = null;

    public int $isDefault = 0;

    public ?DateTime $dateCreated = null;

    public ?DateTime $dateUpdated = null;

    public ?string $uid = null;

    public function __toString()
    {
        return Craft::t('sprout-module-forms', $this->name);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('sprout/settings/orders-statuses/' . $this->id);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        return $rules;
    }
}
