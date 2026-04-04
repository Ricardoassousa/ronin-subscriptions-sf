<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Subscription;
use App\Enum\ActivityLogType;
use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * DashboardService constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Computes key metrics for the admin dashboard.
     *
     * Metrics include:
     *  - Total number of subscriptions
     *  - Number of active subscriptions
     *  - Number of cancelled subscriptions
     *  - Monthly recurring revenue (MRR) grouped by currency
     *
     * Active subscriptions or cancelled subscriptions that have not yet ended
     * are counted towards MRR.
     *
     * @return array{
     *     totalSubscriptions: int,
     *     activeSubscriptions: int,
     *     cancelledSubscriptions: int,
     *     mrr: array<string, float>
     * }
     */
    public function getMetrics(): array
    {
        $subscriptions = $this->em->getRepository(Subscription::class)->findAll();

        $totalSubscriptions = count($subscriptions);
        $activeSubscriptions = 0;
        $cancelledSubscriptions = 0;
        $mrr = [];
        $now = new DateTimeImmutable();

        foreach ($subscriptions as $subscription) {
            $status = $subscription->getStatus();
            $endsAt = $subscription->getEndsAt();

            if ($status === SubscriptionStatus::ACTIVE->value) {
                $activeSubscriptions++;
            }

            if ($status === SubscriptionStatus::CANCELLED->value) {
                $cancelledSubscriptions++;
            }

            if ($status === SubscriptionStatus::ACTIVE->value
                || ($status === SubscriptionStatus::CANCELLED->value && $endsAt !== null && $endsAt > $now)
            ) {
                $amount = $subscription->getPriceSnapshot();
                $currency = $subscription->getCurrencySnapshot();

                if ($subscription->getBillingIntervalSnapshot() === 'year') {
                    $amount /= 12;
                }

                $mrr[$currency] = ($mrr[$currency] ?? 0) + $amount;
            }
        }

        return [
            'totalSubscriptions' => $totalSubscriptions,
            'activeSubscriptions' => $activeSubscriptions,
            'cancelledSubscriptions' => $cancelledSubscriptions,
            'mrr' => $mrr
        ];
    }

    /**
     * Retrieves the most recent activities for the admin dashboard.
     *
     * Each activity includes:
     *  - A human-readable label
     *  - A color code for UI highlighting
     *  - Description, creation timestamp
     *  - Related entity type and ID
     *  - User ID (if available)
     *
     * @param int $limit Maximum number of activities to return (default 20)
     * @return array<int, array{
     *     label: string,
     *     color: string,
     *     description: string|null,
     *     createdAt: DateTimeImmutable,
     *     relatedType: string|null,
     *     relatedId: int|null,
     *     userId: int|null
     * }>
     */
    public function getRecentActivities(int $limit = 20): array
    {
        $recentActivities = $this->em->getRepository(ActivityLog::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);

        $activityFeed = [];
        foreach ($recentActivities as $activity) {
            $typeEnum = ActivityLogType::tryFrom($activity->getType());

            $activityFeed[] = [
                'label' => $typeEnum ? $typeEnum->getLabel() : $activity->getType(),
                'color' => $typeEnum ? $typeEnum->getColor() : 'secondary',
                'description' => $activity->getDescription(),
                'createdAt' => $activity->getCreatedAt(),
                'relatedType' => $activity->getRelatedType(),
                'relatedId' => $activity->getRelatedId(),
                'userId' => $activity->getUser()?->getId()
            ];
        }

        return $activityFeed;
    }

}