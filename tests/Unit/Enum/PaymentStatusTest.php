<?php

namespace App\Tests\Enum;

use App\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function testIsPending(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->isPending());
        $this->assertFalse(PaymentStatus::SUCCESS->isPending());
        $this->assertFalse(PaymentStatus::FAILED->isPending());
        $this->assertFalse(PaymentStatus::REFUNDED->isPending());
    }

    public function testIsSuccess(): void
    {
        $this->assertTrue(PaymentStatus::SUCCESS->isSuccess());
        $this->assertFalse(PaymentStatus::PENDING->isSuccess());
        $this->assertFalse(PaymentStatus::FAILED->isSuccess());
        $this->assertFalse(PaymentStatus::REFUNDED->isSuccess());
    }

    public function testIsFailed(): void
    {
        $this->assertTrue(PaymentStatus::FAILED->isFailed());
        $this->assertFalse(PaymentStatus::PENDING->isFailed());
        $this->assertFalse(PaymentStatus::SUCCESS->isFailed());
        $this->assertFalse(PaymentStatus::REFUNDED->isFailed());
    }

    public function testIsRefunded(): void
    {
        $this->assertTrue(PaymentStatus::REFUNDED->isRefunded());
        $this->assertFalse(PaymentStatus::PENDING->isRefunded());
        $this->assertFalse(PaymentStatus::SUCCESS->isRefunded());
        $this->assertFalse(PaymentStatus::FAILED->isRefunded());
    }

}