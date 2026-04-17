<?php

namespace App\Tests\Enum;

use App\Enum\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

class SubscriptionStatusTest extends TestCase
{
    public function testIsActive(): void
    {
        $this->assertTrue(SubscriptionStatus::ACTIVE->isActive());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isActive());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isActive());
        $this->assertFalse(SubscriptionStatus::PAUSED->isActive());
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isActive());
        $this->assertFalse(SubscriptionStatus::PENDING_PAYMENT->isActive());
    }

    public function testIsCancelled(): void
    {
        $this->assertTrue(SubscriptionStatus::CANCELLED->isCancelled());
        $this->assertFalse(SubscriptionStatus::ACTIVE->isCancelled());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isCancelled());
        $this->assertFalse(SubscriptionStatus::PAUSED->isCancelled());
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isCancelled());
        $this->assertFalse(SubscriptionStatus::PENDING_PAYMENT->isCancelled());
    }

    public function testIsExpired(): void
    {
        $this->assertTrue(SubscriptionStatus::EXPIRED->isExpired());
        $this->assertFalse(SubscriptionStatus::ACTIVE->isExpired());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isExpired());
        $this->assertFalse(SubscriptionStatus::PAUSED->isExpired());
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isExpired());
        $this->assertFalse(SubscriptionStatus::PENDING_PAYMENT->isExpired());
    }

    public function testIsPaused(): void
    {
        $this->assertTrue(SubscriptionStatus::PAUSED->isPaused());
        $this->assertFalse(SubscriptionStatus::ACTIVE->isPaused());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isPaused());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isPaused());
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isPaused());
        $this->assertFalse(SubscriptionStatus::PENDING_PAYMENT->isPaused());
    }

    public function testIsPastDue(): void
    {
        $this->assertTrue(SubscriptionStatus::PAST_DUE->isPastDue());
        $this->assertFalse(SubscriptionStatus::ACTIVE->isPastDue());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isPastDue());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isPastDue());
        $this->assertFalse(SubscriptionStatus::PAUSED->isPastDue());
        $this->assertFalse(SubscriptionStatus::PENDING_PAYMENT->isPastDue());
    }

    public function testIsPendingPayment(): void
    {
        $this->assertTrue(SubscriptionStatus::PENDING_PAYMENT->isPendingPayment());
        $this->assertFalse(SubscriptionStatus::ACTIVE->isPendingPayment());
        $this->assertFalse(SubscriptionStatus::CANCELLED->isPendingPayment());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isPendingPayment());
        $this->assertFalse(SubscriptionStatus::PAUSED->isPendingPayment());
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isPendingPayment());
    }

}