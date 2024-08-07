<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByColorAndCategory(int $colorId, int $categoryId)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->innerJoin('p.productCategories', 'pc')
            ->where('c.id = :colorId')
            ->andWhere('pc.id = :categoryId')
            ->setParameter('colorId', $colorId)
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByColor(int $colorId)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->where('c.id = :colorId')
            ->setParameter('colorId', $colorId)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getColors()
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->select('c.id, c.name')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.productCategories', 'pc')
            ->where('pc.id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getCategories()
    {
        return $this->createQueryBuilder('p')
            ->select('pc.id, pc.name')
            ->innerJoin('p.productCategories', 'pc')
            ->distinct()
            ->getQuery()
            ->getResult();
    }


    private function findSeller($order): ?Product
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.productVariants', 'pv')
            ->join('pv.orderItems', 'oi')
            ->groupBy('p')
            ->orderBy('COUNT(oi)', $order)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    
        return $qb;
    }
    
    public function findBestSeller(): ?Product
    {
        return $this->findSeller('DESC');
    }
    
    public function findWorstSeller(): ?Product
    {
        return $this->findSeller('ASC');
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
