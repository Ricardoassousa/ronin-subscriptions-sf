<?php

namespace App\Tests\Unit\Domain\Subscription;

use App\Domain\Subscription\SubscriptionStatusPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SubscriptionStatusPolicyTest extends TestCase
{
    public function testShouldExpireWhenBillingDateIsInThePast(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $now = new DateTimeImmutable('2026-01-10 00:00:00');
        $nextBilling = new DateTimeImmutable('2026-01-01 00:00:00');

        $this->assertTrue(
            $policy->shouldExpire($now, $nextBilling)
        );
    }

    public function testShouldNotExpireWhenBillingDateIsInFuture(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $now = new DateTimeImmutable('2026-01-01 00:00:00');
        $nextBilling = new DateTimeImmutable('2026-01-10 00:00:00');

        $this->assertFalse(
            $policy->shouldExpire($now, $nextBilling)
        );
    }

    public function testShouldNotExpireWhenDatesAreEqual(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $now = new DateTimeImmutable('2026-01-01 10:00:00');
        $nextBilling = new DateTimeImmutable('2026-01-01 10:00:00');

        $this->assertFalse(
            $policy->shouldExpire($now, $nextBilling)
        );
    }

    public function testShouldNotExpireWhenNextBillingIsNull(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $now = new DateTimeImmutable();

        $this->assertFalse(
            $policy->shouldExpire($now, null)
        );
    }

    public function testIsActiveReturnsTrueWhenStatusIsActive(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $this->assertTrue(
            $policy->isActive('active')
        );
    }

    public function testIsActiveReturnsFalseWhenStatusIsNotActive(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $this->assertFalse(
            $policy->isActive('expired')
        );
    }

    public function testIsExpiredReturnsTrueWhenStatusIsExpired(): void
    {
        $policy = new SubscriptionStatusPolicy();

        $this->assertTrue(
            $policy->isExpired('expired')
        );
    }

}