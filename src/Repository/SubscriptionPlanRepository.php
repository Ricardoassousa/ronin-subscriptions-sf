<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * Returns a Query for filtering subscription plans
     *
     * @param array $searchParams
     * @return Query
     */
    public function findSubscriptionPlanByFilterQuery(array $searchParams): Query
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('subscriptionPlan')
            ->from(SubscriptionPlan::class, 'subscriptionPlan');

        if (array_key_exists('search', $searchParams) && !empty($searchParams['search'])) {
            $qb->andWhere('subscriptionPlan.name LIKE :search OR subscriptionPlan.slug LIKE :search')
               ->setParameter('search', '%' . $searchParams['search'] . '%');
        }

        if (array_key_exists('isActive', $searchParams)) {
            $qb->andWhere('subscriptionPlan.isActive = :isActive')
               ->setParameter('isActive', $searchParams['isActive']);
        }

        if (array_key_exists('billingInterval', $searchParams) && !empty($searchParams['billingInterval'])) {
            $qb->andWhere('subscriptionPlan.billingInterval = :interval')
               ->setParameter('interval', $searchParams['billingInterval']);
        }

        if (array_key_exists('minPrice', $searchParams) && array_key_exists('maxPrice', $searchParams)) {
            $qb->andWhere('subscriptionPlan.price BETWEEN :minPrice AND :maxPrice')
               ->setParameter('minPrice', $searchParams['minPrice'])
               ->setParameter('maxPrice', $searchParams['maxPrice']);
        } elseif (array_key_exists('minPrice', $searchParams)) {
            $qb->andWhere('subscriptionPlan.price >= :minPrice')
               ->setParameter('minPrice', $searchParams['minPrice']);
        } elseif (array_key_exists('maxPrice', $searchParams)) {
            $qb->andWhere('subscriptionPlan.price <= :maxPrice')
               ->setParameter('maxPrice', $searchParams['maxPrice']);
        }

        if (array_key_exists('isFeatured', $searchParams)) {
            $qb->andWhere('subscriptionPlan.isFeatured = :featured')
               ->setParameter('featured', $searchParams['isFeatured']);
        }

        if (array_key_exists('minTrialDays', $searchParams) && array_key_exists('maxTrialDays', $searchParams)) {
            $qb->andWhere('subscriptionPlan.trialDays BETWEEN :minTrial AND :maxTrial')
               ->setParameter('minTrial', $searchParams['minTrialDays'])
               ->setParameter('maxTrial', $searchParams['maxTrialDays']);
        } elseif (array_key_exists('minTrialDays', $searchParams)) {
            $qb->andWhere('subscriptionPlan.trialDays >= :minTrial')
               ->setParameter('minTrial', $searchParams['minTrialDays']);
        } elseif (array_key_exists('maxTrialDays', $searchParams)) {
            $qb->andWhere('subscriptionPlan.trialDays <= :maxTrial')
               ->setParameter('maxTrial', $searchParams['maxTrialDays']);
        }

        $qb->orderBy('subscriptionPlan.sortOrder', 'ASC')
           ->addOrderBy('subscriptionPlan.id', 'DESC');

        return $qb->getQuery();
    }

}