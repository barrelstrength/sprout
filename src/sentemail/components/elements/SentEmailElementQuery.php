<?php

namespace BarrelStrength\Sprout\sentemail\components\elements;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SentEmailElementQuery extends ElementQuery
{
    protected array $defaultOrderBy = [
        'sprout_sent_emails.dateCreated' => SORT_DESC,
    ];

    public ?string $subjectLine = null;

    public ?string $fromName = null;

    public ?string $fromEmail = null;

    public ?string $toEmail = null;

    public function subjectLine(string $value): self
    {
        $this->subjectLine = $value;

        return $this;
    }

    public function fromName(string $value): self
    {
        $this->fromName = $value;

        return $this;
    }

    public function fromEmail(string $value): self
    {
        $this->fromEmail = $value;

        return $this;
    }

    public function toEmail(string $value): self
    {
        $this->toEmail = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sprout_sent_emails');

        $this->query->select([
            'sprout_sent_emails.id',
            'sprout_sent_emails.title',
            'sprout_sent_emails.subjectLine',
            'sprout_sent_emails.fromEmail',
            'sprout_sent_emails.fromName',
            'sprout_sent_emails.toEmail',
            'sprout_sent_emails.textBody',
            'sprout_sent_emails.htmlBody',
            'sprout_sent_emails.info',
            'sprout_sent_emails.sent',
            'sprout_sent_emails.dateCreated',
            'sprout_sent_emails.dateUpdated',
        ]);

        if ($this->subjectLine) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_sent_emails.subjectLine', $this->subjectLine)
            );
        }

        if ($this->fromName) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_sent_emails.fromName', $this->fromName)
            );
        }

        if ($this->fromEmail) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_sent_emails.fromEmail', $this->fromEmail)
            );
        }

        if ($this->toEmail) {
            $this->subQuery->andWhere(Db::parseParam(
                'sprout_sent_emails.toEmail', $this->toEmail)
            );
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            SentEmailElement::STATUS_SENT => [
                'sprout_sent_emails.sent' => true,
            ],
            SentEmailElement::STATUS_FAILED => [
                'sprout_sent_emails.sent' => false,
            ],
            default => false,
        };
    }
}
