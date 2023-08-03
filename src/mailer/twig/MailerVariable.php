<?php

namespace BarrelStrength\Sprout\mailer\twig;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElementQuery;
use Craft;

class MailerVariable
{
    public function audiences(array $criteria = []): AudienceElementQuery
    {
        $query = AudienceElement::find();
        Craft::configure($query, $criteria);

        return $query;
    }
}
