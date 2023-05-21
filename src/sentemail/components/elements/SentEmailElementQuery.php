<?php

namespace BarrelStrength\Sprout\sentemail\components\elements;

use craft\elements\db\ElementQuery;

class SentEmailElementQuery extends ElementQuery
{
    protected array $defaultOrderBy = [
        'sprout_sent_emails.dateCreated' => SORT_DESC,
    ];

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
