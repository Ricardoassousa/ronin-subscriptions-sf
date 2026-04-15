<?php

namespace App\Tests\Service;

use App\Entity\ActivityLog;
use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
use App\Enum\ActivityLogType;
use App\Service\DashboardService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class DashboardServiceTest extends TestCase
{
    private DashboardService $dashboardService;
    private EntityManagerInterface $entityManager;
    private EntityRepository $subscriptionRepository;
    private EntityRepository $activityLogRepository;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(EntityRepository::class);
        $this->activityLogRepository = $this->createMock(EntityRepository::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')
            ->willReturnCallback(fn($class) => match ($class) {
                Subscription::class => $this->subscriptionRepository,
                ActivityLog::class => $this->activityLogRepository,
            });

        $this->dashboardService = new DashboardService($this->entityManager);
    }

    public function testGetMetricsCalculatesCorrectly(): void
    {
        $now = new DateTimeImmutable();

        $subscriptionActive = $this->createMock(Subscription::class);
        $subscriptionActive->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);
        $subscriptionActive->method('getPriceSnapshot')->willReturn(120.0);
        $subscriptionActive->method('getCurrencySnapshot')->willReturn('USD');
        $subscriptionActive->method('getBillingIntervalSnapshot')->willReturn('year');
        $subscriptionActive->method('getEndsAt')->willReturn(null);

        $subscriptionCancelled = $this->createMock(Subscription::class);
        $subscriptionCancelled->method('getStatus')->willReturn(SubscriptionStatus::CANCELLED->value);
        $subscriptionCancelled->method('getPriceSnapshot')->willReturn(50.0);
        $subscriptionCancelled->method('getCurrencySnapshot')->willReturn('EUR');
        $subscriptionCancelled->method('getBillingIntervalSnapshot')->willReturn('month');
        $subscriptionCancelled->method('getEndsAt')->willReturn($now->modify('+1 day'));

        $this->subscriptionRepository->method('findAll')->willReturn([
            $subscriptionActive,
            $subscriptionCancelled
        ]);

        $metrics = $this->dashboardService->getMetrics();

        $this->assertEquals(2, $metrics['totalSubscriptions']);
        $this->assertEquals(1, $metrics['activeSubscriptions']);
        $this->assertEquals(1, $metrics['cancelledSubscriptions']);
        $this->assertEquals(10.0, $metrics['mrr']['USD']); // 120 / 12
        $this->assertEquals(50.0, $metrics['mrr']['EUR']);
    }

    public function testGetRecentActivitiesFormatsCorrectly(): void
    {
        $activity = $this->createMock(ActivityLog::class);
        $activity->method('getType')->willReturn(ActivityLogType::SUBSCRIPTION_CREATED->value);
        $activity->method('getDescription')->willReturn('User subscribed');
        $activity->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $activity->method('getRelatedType')->willReturn('Subscription');
        $activity->method('getRelatedId')->willReturn(1);
        $activity->method('getUser')->willReturn(null);

        $this->activityLogRepository->method('findBy')->willReturn([$activity]);

        $activities = $this->dashboardService->getRecentActivities(10);

        $this->assertCount(1, $activities);
        $this->assertEquals('Subscription created', $activities[0]['label']);
        $this->assertEquals('success', $activities[0]['color']);
        $this->assertEquals('User subscribed', $activities[0]['description']);
        $this->assertEquals('Subscription', $activities[0]['relatedType']);
        $this->assertEquals(1, $activities[0]['relatedId']);
        $this->assertNull($activities[0]['userId']);
    }

    public function testGetRecentActivitiesHandlesUnknownTypeGracefully(): void
    {
        $activity = $this->createMock(ActivityLog::class);
        $activity->method('getType')->willReturn('UNKNOWN_TYPE');
        $activity->method('getDescription')->willReturn('Something happened');
        $activity->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $activity->method('getRelatedType')->willReturn(null);
        $activity->method('getRelatedId')->willReturn(null);
        $activity->method('getUser')->willReturn(null);

        $this->activityLogRepository->method('findBy')->willReturn([$activity]);

        $activities = $this->dashboardService->getRecentActivities();

        $this->assertEquals('UNKNOWN_TYPE', $activities[0]['label']);
        $this->assertEquals('secondary', $activities[0]['color']);
    }

}