<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
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

    /**
     * Finds all subscriptions for a specific user, ordered by the started date.
     *
     * @param User $user
     * @return Subscription[]
     */
    public function findSubscriptionsByUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('subscription')
            ->from(Subscription::class, 'subscription')
            ->where('subscription.user = :user')
            ->setParameter('user', $user)
            ->orderBy('subscription.startedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds the current subscription for a given user.
     *
     * This method returns the most recent subscription of the user
     * that is still valid according to business rules (ACTIVE, PAUSED, PAST_DUE).
     *
     * @param User $user
     * @return Subscription|null
     */
    public function findCurrentSubscriptionByUser(User $user): ?Subscription
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('subscription')
            ->from(Subscription::class, 'subscription')
            ->where('subscription.user = :user')
            ->andWhere('subscription.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::PAUSED->value,
                SubscriptionStatus::PAST_DUE->value,
            ])
            ->orderBy('subscription.startedAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
