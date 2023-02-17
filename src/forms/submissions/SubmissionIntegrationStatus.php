<?php

namespace BarrelStrength\Sprout\forms\submissions;

abstract class SubmissionIntegrationStatus
{
    public const SUBMISSION_INTEGRATION_PENDING_STATUS = 'pending';

    public const SUBMISSION_INTEGRATION_NOT_SENT_STATUS = 'notsent';

    public const SUBMISSION_INTEGRATION_COMPLETED_STATUS = 'completed';
}
