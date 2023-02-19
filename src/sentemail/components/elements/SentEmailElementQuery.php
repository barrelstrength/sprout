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
            'sprout_sent_emails.status',
            'sprout_sent_emails.dateCreated',
            'sprout_sent_emails.dateUpdated',
        ]);

        return parent::beforePrepare();
    }
}
