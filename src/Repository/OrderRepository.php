<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Order;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @param User $user
     * @return Order[]
     */
    public function findUnpaidOrders(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.isPaid = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function replaceUserInOrders(User $userToReplace, User $replacementUser): void
    {
        $qb = $this->createQueryBuilder('o');
        $qb->update()
            ->set('o.user', ':replacementUser')
            ->where('o.user = :userToReplace')
            ->setParameter('replacementUser', $replacementUser)
            ->setParameter('userToReplace', $userToReplace)
            ->getQuery()
            ->execute();
    }

    public function getTotalPrice(): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) AS total')
            ->getQuery();

        $result = $qb->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function findAllPaidOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isPaid = :isPaid')
            ->setParameter('isPaid', true)
            ->orderBy('o.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalPaidOrdersPrice(): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) AS total')
            ->where('o.isPaid = :isPaid')
            ->setParameter('isPaid', true)
            ->getQuery();

        $result = $qb->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function findAllUnpaidOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isPaid = :isPaid')
            ->setParameter('isPaid', false)
            ->orderBy('o.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalUnpaidOrdersPrice(): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) AS total')
            ->where('o.isPaid = :isPaid')
            ->setParameter('isPaid', false)
            ->getQuery();

        $result = $qb->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
