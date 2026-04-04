<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
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

    /**
     * Finds an active or past due subscription by user email.
     *
     * This method retrieves a subscription associated with the given email,
     * but only if its status is ACTIVE or PAST_DUE.
     *
     * @param string $email
     * @return Subscription|null
     */
    public function findActiveOrPastDueByEmail(string $email): ?Subscription
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('subscription')
            ->from(Subscription::class, 'subscription')
            ->join('subscription.user', 'user')
            ->where('user.email = :email')
            ->andWhere('subscription.status IN (:statuses)')
            ->setParameter('email', $email)
            ->setParameter('statuses', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::PAST_DUE->value
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
