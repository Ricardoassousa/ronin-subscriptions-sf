<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * AnalyticsService constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Monthly MRR with correct business logic (PHP-based).
     */
    public function getMonthlyMRR(int $months = 6): array
    {
        $subscriptions = $this->em->getRepository(Subscription::class)->findAll();

        $now = new DateTimeImmutable();
        $start = $now->modify("-{$months} months");

        $result = [];

        foreach ($subscriptions as $sub) {
            $createdAt = $sub->getCreatedAt();

            if ($createdAt < $start) {
                continue;
            }

            $status = $sub->getStatus();
            $endsAt = $sub->getEndsAt();

            if (!(
                $status === SubscriptionStatus::ACTIVE->value ||
                ($status === SubscriptionStatus::CANCELLED->value && $endsAt && $endsAt > $now)
            )) {
                continue;
            }

            $month = $createdAt->format('Y-m');

            $amount = $sub->getPriceSnapshot();

            if ($sub->getBillingIntervalSnapshot() === 'year') {
                $amount /= 12;
            }

            $currency = $sub->getCurrencySnapshot();

            $result[$month][$currency] = ($result[$month][$currency] ?? 0) + $amount;
        }

        // Fill missing months
        $filled = [];
        $cursor = $start;

        while ($cursor <= $now) {
            $key = $cursor->format('Y-m');
            $filled[$key] = $result[$key] ?? [];
            $cursor = $cursor->modify('+1 month');
        }

        ksort($filled);

        return $filled;
    }

    /**
     * Monthly churn (cancelled subscriptions)
     */
    public function getMonthlyChurn(int $months = 6): array
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT 
                TO_CHAR(created_at, 'YYYY-MM') AS month,
                COUNT(*) AS cancelled
            FROM subscription
            WHERE status = :status
              AND created_at >= NOW() - INTERVAL '1 month' * :months
            GROUP BY month
            ORDER BY month ASC
        ";

        return $conn->executeQuery($sql, [
            'status' => SubscriptionStatus::CANCELLED->value,
            'months' => $months
        ])->fetchAllAssociative();
    }

    /**
     * Monthly growth (new subscriptions)
     */
    public function getMonthlyGrowth(int $months = 6): array
    {
        $conn = $this->em->getConnection();

        $sql = "
            SELECT 
                TO_CHAR(created_at, 'YYYY-MM') AS month,
                COUNT(*) AS total
            FROM subscription
            WHERE created_at >= NOW() - INTERVAL '1 month' * :months
            GROUP BY month
            ORDER BY month ASC
        ";

        return $conn->executeQuery($sql, [
            'months' => $months
        ])->fetchAllAssociative();
    }

}