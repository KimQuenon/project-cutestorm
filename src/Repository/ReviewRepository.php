<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Product;
use App\Entity\OrderItem;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    // average rating with decimal
    public function getAverageRating(int $productId): ?float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->where('r.product = :productId')
            ->setParameter('productId', $productId);

        // Get the result as a single scalar value
        $result = $qb->getQuery()->getSingleScalarResult();

        // Return the average rating formatted to one decimal place
        return $result ? round((float) $result, 1) : null;
    }

    // has the user bought that product?
    public function hasUserBoughtProduct(User $user, Product $product)
    {
        if ($user === null) {
            return false;
        }
        
        $query = $this->getEntityManager()->getRepository(OrderItem::class)
            ->createQueryBuilder('oi')
            ->select('1') // select a dummy value, we only care about the existence
            ->innerJoin('oi.orderRelated', 'o')
            ->innerJoin('oi.productVariant', 'pv')
            ->where('o.user = :user')
            ->andWhere('pv.product = :product')
            ->andWhere('o.isPaid = true')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setMaxResults(1); // we only need to know if at least one row exists
    
        return $query->getQuery()->getOneOrNullResult() !== null;
    }

    // find review rated between 4 & 5 stars
    public function findTopRatedReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.rating >= :minRating')
            ->setParameter('minRating', 4)
            ->orderBy('r.rating', 'DESC')
            ->addOrderBy('r.timestamp', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Review[] Returns an array of Review objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Review
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
