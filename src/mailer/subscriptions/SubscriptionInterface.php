<?php

namespace BarrelStrength\Sprout\mailer\subscriptions;

interface SubscriptionInterface
{
    public const SCENARIO_SUBSCRIBER = 'subscriber';

    public function getId(): ?int;
}
