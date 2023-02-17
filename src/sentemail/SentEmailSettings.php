<?php

namespace BarrelStrength\Sprout\sentemail;

use craft\config\BaseConfig;

class SentEmailSettings extends BaseConfig
{
    public int $sentEmailsLimit = 5000;

    public int $cleanupProbability = 1000;

    public function sentEmailsLimit(int $value): self
    {
        $this->sentEmailsLimit = $value;

        return $this;
    }

    public function cleanupProbability(int $value): self
    {
        $this->cleanupProbability = $value;

        return $this;
    }
}

