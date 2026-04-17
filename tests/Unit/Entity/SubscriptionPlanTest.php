<?php

namespace App\Tests\Entity;

use App\Entity\SubscriptionPlan;
use PHPUnit\Framework\TestCase;

class SubscriptionPlanTest extends TestCase
{
    public function testGetBillingLabel(): void
    {
        $plan = new SubscriptionPlan();

        $plan->setBillingInterval('month');
        $this->assertSame('Billed monthly', $plan->getBillingLabel());

        $plan->setBillingInterval('year');
        $this->assertSame('Billed yearly', $plan->getBillingLabel());

        $plan->setBillingInterval('weekly');
        $this->assertSame('Custom billing', $plan->getBillingLabel());
    }

}