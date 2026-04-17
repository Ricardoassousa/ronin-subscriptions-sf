<?php

use App\Service\SubscriptionService;
use App\Entity\User;
use App\Entity\SubscriptionPlan;
use App\Enum\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class SubscriptionServiceTest extends TestCase
{
    private $em;
    private $logger;
    private SubscriptionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new SubscriptionService($this->em, $this->logger);
    }

    public function testSubscribeCreatesSubscriptionSuccessfully(): void
    {
        $user = $this->createMock(User::class);
        $plan = $this->createMock(SubscriptionPlan::class);

        $user->method('getId')->willReturn(1);
        $plan->method('getId')->willReturn(1);
        $plan->method('getPrice')->willReturn(100.0);
        $plan->method('getCurrency')->willReturn('USD');
        $plan->method('getBillingInterval')->willReturn('month');
        $plan->method('getDiscountPercent')->willReturn(null);
        $plan->method('getTrialDays')->willReturn(0);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $subscription = $this->service->subscribe($user, $plan);

        $this->assertEquals(SubscriptionStatus::PENDING_PAYMENT->value, $subscription->getStatus());
        $this->assertEquals(100.0, $subscription->getPriceSnapshot());
        $this->assertEquals('USD', $subscription->getCurrencySnapshot());
    }

}