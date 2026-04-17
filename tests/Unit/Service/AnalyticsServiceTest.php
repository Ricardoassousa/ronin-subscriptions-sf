<?php

namespace App\Tests\Service;

use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
use App\Service\AnalyticsService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class AnalyticsServiceTest extends TestCase
{
    private AnalyticsService $service;
    private $emMock;
    private $repositoryMock;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(EntityRepository::class);
        $this->emMock->method('getRepository')->willReturn($this->repositoryMock);

        $this->service = new AnalyticsService($this->emMock);
    }

    public function testGetMonthlyMRRCalculatesCorrectly(): void
    {
        $now = new DateTimeImmutable('2026-04-01');

        // Active monthly subscription
        $sub1 = $this->createMock(Subscription::class);
        $sub1->method('getCreatedAt')->willReturn($now->modify('-1 month'));
        $sub1->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);
        $sub1->method('getEndsAt')->willReturn(null);
        $sub1->method('getPriceSnapshot')->willReturn(100.0);
        $sub1->method('getCurrencySnapshot')->willReturn('USD');
        $sub1->method('getBillingIntervalSnapshot')->willReturn('month');

        // Active yearly subscription
        $sub2 = $this->createMock(Subscription::class);
        $sub2->method('getCreatedAt')->willReturn($now->modify('-2 month'));
        $sub2->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);
        $sub2->method('getEndsAt')->willReturn(null);
        $sub2->method('getPriceSnapshot')->willReturn(1200.0);
        $sub2->method('getCurrencySnapshot')->willReturn('USD');
        $sub2->method('getBillingIntervalSnapshot')->willReturn('year');

        // Cancelled subscription still valid
        $sub3 = $this->createMock(Subscription::class);
        $sub3->method('getCreatedAt')->willReturn($now->modify('-1 month'));
        $sub3->method('getStatus')->willReturn(SubscriptionStatus::CANCELLED->value);
        $sub3->method('getEndsAt')->willReturn($now->modify('+1 month'));
        $sub3->method('getPriceSnapshot')->willReturn(200.0);
        $sub3->method('getCurrencySnapshot')->willReturn('EUR');
        $sub3->method('getBillingIntervalSnapshot')->willReturn('month');

        // Subscription outside the period
        $sub4 = $this->createMock(Subscription::class);
        $sub4->method('getCreatedAt')->willReturn($now->modify('-12 month'));
        $sub4->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);
        $sub4->method('getEndsAt')->willReturn(null);
        $sub4->method('getPriceSnapshot')->willReturn(50.0);
        $sub4->method('getCurrencySnapshot')->willReturn('USD');
        $sub4->method('getBillingIntervalSnapshot')->willReturn('month');

        $this->repositoryMock->method('findAll')->willReturn([$sub1, $sub2, $sub3, $sub4]);

        $mrr = $this->service->getMonthlyMRR(6);

        // Fill months exactly as the service does
        $start = $now->modify('-6 months');
        $expected = [];
        $cursor = $start;
        while ($cursor <= $now) {
            $key = $cursor->format('Y-m');
            $expected[$key] = [];
            $cursor = $cursor->modify('+1 month');
        }

        // Map subscription values to their months
        $monthSub1 = $sub1->getCreatedAt()->format('Y-m');
        $monthSub2 = $sub2->getCreatedAt()->format('Y-m');
        $monthSub3 = $sub3->getCreatedAt()->format('Y-m');

        $expected[$monthSub1]['USD'] = 100.0;
        $expected[$monthSub2]['USD'] = 1200.0 / 12; // Annual subscription divided by 12
        $expected[$monthSub3]['EUR'] = 200.0;

        $this->assertEquals($expected, $mrr);
    }

}