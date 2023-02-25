<?php

namespace BarrelStrength\Sprout\mailer\subscribers;

interface SubscriptionInterface
{
    public const SCENARIO_SUBSCRIBER = 'subscriber';

    public function getId(): ?int;
}
