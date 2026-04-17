<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\CustomerProfile;
use App\Service\CustomerProfileService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerProfileServiceTest extends TestCase
{
    private CustomerProfileService $service;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new CustomerProfileService($this->logger);
    }

    public function testHasCustomerProfileReturnsTrueWhenUserHasProfile(): void
    {
        $customerProfile = $this->createMock(CustomerProfile::class);

        $user = $this->createMock(User::class);
        $user->method('getCustomerProfile')->willReturn($customerProfile);

        $result = $this->service->hasCustomerProfile($user);

        $this->assertTrue($result);
    }

    public function testHasCustomerProfileReturnsFalseAndLogsWarningWhenNoProfile(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCustomerProfile')->willReturn(null);
        $user->method('getId')->willReturn(42);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'User does not have a customer profile.',
                $this->callback(fn($context) => $context['user_id'] === 42)
            );

        $result = $this->service->hasCustomerProfile($user);

        $this->assertFalse($result);
    }

}