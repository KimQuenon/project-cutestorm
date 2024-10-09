<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    //find all products by desc order except the delete one
    public function findAllProducts()
    {
        return $this->createQueryBuilder('p')
            ->where('p.name != :excludedName')
            ->setParameter('excludedName', 'Deleted Product')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // add a filter by category and color
    public function findByColorAndCategory(int $colorId, int $categoryId, array $orderBy = ['id' => 'DESC'])
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->innerJoin('p.productCategories', 'pc')
            ->where('c.id = :colorId')
            ->andWhere('pc.id = :categoryId')
            ->setParameter('colorId', $colorId)
            ->setParameter('categoryId', $categoryId);
    
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy('p.' . $field, strtoupper($order));
        }
    
        return $qb->getQuery()->getResult();
    }
    
    // filter color
    public function findByColor(int $colorId, array $orderBy = ['id' => 'DESC'])
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->where('c.id = :colorId')
            ->setParameter('colorId', $colorId);
    
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy('p.' . $field, strtoupper($order));
        }
    
        return $qb->getQuery()->getResult();
    }
    
    // display all colors available
    public function getColors()
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.color', 'c')
            ->select('c.id, c.name')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    // filter category
    public function findByCategory(int $categoryId, array $orderBy = ['id' => 'DESC'])
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.productCategories', 'pc')
            ->where('pc.id = :categoryId')
            ->setParameter('categoryId', $categoryId);
    
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy('p.' . $field, strtoupper($order));
        }
    
        return $qb->getQuery()->getResult();
    }
    
    // display all categories available
    public function getCategories()
    {
        return $this->createQueryBuilder('p')
            ->select('pc.id, pc.name')
            ->innerJoin('p.productCategories', 'pc')
            ->distinct()
            ->getQuery()
            ->getResult();
    }


    // finding best and worst seller
    private function findSeller($order): ?Product
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.productVariants', 'pv')
            ->join('pv.orderItems', 'oi')
            ->where('p.name != :excludedName')
            ->setParameter('excludedName', 'Deleted Product')
            ->groupBy('p')
            ->orderBy('COUNT(oi)', $order)
            ->setMaxResults(1)
            ->getQuery();
    
        return $qb->getOneOrNullResult();
    }
    
    // parameters for best
    public function findBestSeller(): ?Product
    {
        return $this->findSeller('DESC');
    }
    
    // parameters for worst
    public function findWorstSeller(): ?Product
    {
        return $this->findSeller('ASC');
    }

    // for searchbar => find by product name
    public function findByProductNameQuery(string $term): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :term')
            ->setParameter('term', '%' . $term . '%');
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
