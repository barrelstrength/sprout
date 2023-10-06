<?php

namespace BarrelStrength\Sprout\forms\db;

abstract class SproutTable
{
    public const FORMS = '{{%sprout_forms}}';

    public const FORM_INTEGRATIONS = '{{%sprout_form_integrations}}';

    public const FORM_INTEGRATIONS_LOG = '{{%sprout_form_integrations_log}}';

    public const FORM_SUBMISSIONS_STATUSES = '{{%sprout_form_submissions_statuses}}';

    public const FORM_SUBMISSIONS = '{{%sprout_form_submissions}}';

    public const FORM_SUBMISSIONS_SPAM_LOG = '{{%sprout_form_submissions_spam_log}}';

    // @todo - remove once address updated
    public const ADDRESSES = '{{%sprout_addresses}}';
}
