<?php

namespace App\Repository;

use App\Entity\Subscription;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Finds subscriptions that are due to renew soon.
     *
     * This method retrieves all subscriptions whose next billing date
     * is less than or equal to the given date and are currently active.
     * Typically used for sending renewal reminder notifications.
     *
     * @param DateTimeImmutable $date
     *
     * @return Subscription[]
     */
    public function findRenewingSoon(DateTimeImmutable $date): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('subscription')
            ->from(Subscription::class, 'subscription')
            ->where('subscription.nextBillingAt <= :date')
            ->setParameter('date', $date)
            ->andWhere('subscription.status = :status')
            ->setParameter('status', 'active');

        return $qb->getQuery()->getResult();
    }

}
