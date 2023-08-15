<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class EmailElementQuery extends ElementQuery
{
    public ?string $subjectLine = null;

    public ?string $type = null;

    public ?string $emailThemeUid = null;

    public ?string $mailerUid = null;

    public function subjectLine(string $value): static
    {
        $this->subjectLine = $value;

        return $this;
    }

    public function type(string $value): static
    {
        $this->type = $value;

        return $this;
    }

    public function emailThemeUid(string $value): static
    {
        $this->emailThemeUid = $value;

        return $this;
    }

    public function mailerUid(string $value): static
    {
        $this->mailerUid = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_emails');

        $this->query->select([
            'sprout_emails.subjectLine',
            'sprout_emails.preheaderText',
            'sprout_emails.defaultMessage',
            'sprout_emails.type',
            'sprout_emails.emailTypeSettings',
            'sprout_emails.mailerUid',
            'sprout_emails.mailerInstructionsSettings',
            'sprout_emails.emailThemeUid',
            'sprout_emails.dateCreated',
            'sprout_emails.dateUpdated',
        ]);

        if ($this->subjectLine) {
            $this->subQuery->andWhere(Db::parseParam('sprout_emails.subjectLine', $this->subjectLine));
        }

        if ($this->type) {
            $this->subQuery->andWhere(Db::parseParam('sprout_emails.type', $this->type));
        }

        if ($this->emailThemeUid) {
            $this->subQuery->andWhere(Db::parseParam('sprout_emails.emailThemeUid', $this->emailThemeUid));
        }

        if ($this->mailerUid) {
            $this->subQuery->andWhere(Db::parseParam('sprout_emails.mailerUid', $this->mailerUid));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        if (!$this->type) {
            return parent::statusCondition($status);
        }

        /** @var EmailType $emailType */
        $emailType = new $this->type();

        if (!$emailType->hasCustomStatuses()) {
            return parent::statusCondition($status);
        }

        return $emailType->getStatusCondition($status);
    }
}
