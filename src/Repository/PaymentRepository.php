<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Get payments for a specific user, ordered by creation date.
     *
     * @param User $user
     * @return Payment[]
     */
    public function findPaymentsByUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('payment')
            ->from(Payment::class, 'payment')
            ->where('payment.user = :user')
            ->setParameter('user', $user)
            ->orderBy('payment.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

}
