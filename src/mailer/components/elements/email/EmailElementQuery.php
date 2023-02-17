<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\mailer\email\EmailType;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class EmailElementQuery extends ElementQuery
{
    public ?string $emailType = null;

    public function emailType(string $value): static
    {
        $this->emailType = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_emails');

        $this->query->select([
            'sprout_emails.subjectLine',
            'sprout_emails.preheaderText',
            'sprout_emails.defaultBody',
            'sprout_emails.emailThemeId',
            'sprout_emails.mailerId',
            'sprout_emails.mailerInstructionsSettings',
            'sprout_emails.emailType',
            'sprout_emails.emailTypeSettings',
            'sprout_emails.dateCreated',
            'sprout_emails.dateUpdated',
        ]);

        if ($this->emailType) {
            $this->subQuery->andWhere(Db::parseParam('sprout_emails.emailType', $this->emailType));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        if (!$this->emailType) {
            return parent::statusCondition($status);
        }

        /** @var EmailType $emailType */
        $emailType = new $this->emailType();

        if (!$emailType->hasCustomStatuses()) {
            return parent::statusCondition($status);
        }

        return $emailType->getStatusCondition($status);
    }
}
