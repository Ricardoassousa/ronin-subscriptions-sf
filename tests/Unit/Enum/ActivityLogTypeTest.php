<?php

namespace App\Tests\Enum;

use App\Enum\ActivityLogType;
use PHPUnit\Framework\TestCase;

class ActivityLogTypeTest extends TestCase
{
    public function testGetLabelReturnsCorrectValue(): void
    {
        $this->assertSame('Subscription created', ActivityLogType::SUBSCRIPTION_CREATED->getLabel());
        $this->assertSame('Payment failed', ActivityLogType::PAYMENT_FAILED->getLabel());
        $this->assertSame('Invoice paid', ActivityLogType::INVOICE_PAID->getLabel());
        $this->assertSame('Plan toggled', ActivityLogType::PLAN_TOGGLED->getLabel());
        $this->assertSame('User registered', ActivityLogType::USER_REGISTERED->getLabel());
        $this->assertSame('Login failed', ActivityLogType::LOGIN_FAILED->getLabel());
    }

    public function testGetColorReturnsCorrectValue(): void
    {
        $this->assertSame('danger', ActivityLogType::PAYMENT_FAILED->getColor());
        $this->assertSame('success', ActivityLogType::PAYMENT_SUCCEEDED->getColor());
        $this->assertSame('info', ActivityLogType::PAYMENT_REFUNDED->getColor());
        $this->assertSame('warning', ActivityLogType::SUBSCRIPTION_CANCELLED->getColor());
        $this->assertSame('secondary', ActivityLogType::PLAN_UPDATED->getColor());
    }

    public function testGetRelatedTypeReturnsCorrectValue(): void
    {
        $this->assertSame('Subscription', ActivityLogType::SUBSCRIPTION_CREATED->getRelatedType());
        $this->assertSame('Payment', ActivityLogType::PAYMENT_SUCCEEDED->getRelatedType());
        $this->assertSame('Invoice', ActivityLogType::INVOICE_CREATED->getRelatedType());
        $this->assertSame('Plan', ActivityLogType::PLAN_UPDATED->getRelatedType());
        $this->assertSame('User', ActivityLogType::USER_REGISTERED->getRelatedType());
        $this->assertNull(ActivityLogType::EMAIL_DUPLICATE_ATTEMPT->getRelatedType());
    }

    public function testIsFailureReturnsCorrectValue(): void
    {
        $this->assertTrue(ActivityLogType::PAYMENT_FAILED->isFailure());
        $this->assertTrue(ActivityLogType::INVOICE_FAILED->isFailure());
        $this->assertTrue(ActivityLogType::LOGIN_FAILED->isFailure());

        $this->assertFalse(ActivityLogType::PAYMENT_SUCCEEDED->isFailure());
        $this->assertFalse(ActivityLogType::SUBSCRIPTION_CREATED->isFailure());
        $this->assertFalse(ActivityLogType::PLAN_CREATED->isFailure());
    }

    public function testIsBillingRelatedReturnsCorrectValue(): void
    {
        $this->assertTrue(ActivityLogType::SUBSCRIPTION_CREATED->isBillingRelated());
        $this->assertTrue(ActivityLogType::PAYMENT_SUCCEEDED->isBillingRelated());
        $this->assertTrue(ActivityLogType::INVOICE_PAID->isBillingRelated());

        $this->assertFalse(ActivityLogType::LOGIN_FAILED->isBillingRelated());
        $this->assertFalse(ActivityLogType::USER_REGISTERED->isBillingRelated());
        $this->assertFalse(ActivityLogType::PLAN_CREATED->isBillingRelated());
    }

}