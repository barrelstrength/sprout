<?php

namespace BarrelStrength\Sprout\mailer\db;

abstract class SproutTable
{
    public const EMAILS = '{{%sprout_emails}}';

    public const EMAIL_THEMES = '{{%sprout_email_themes}}';

    public const MAILERS = '{{%sprout_mailers}}';

    public const AUDIENCES = '{{%sprout_audiences}}';

    public const AUDIENCE_GROUPS = '{{%sprout_audience_groups}}';

    public const SUBSCRIPTIONS = '{{%sprout_subscriptions}}';
}
