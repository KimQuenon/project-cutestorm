<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Report;
use App\Entity\Comment;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function hasUserReportedPost(User $user, Post $post): bool
    {
        $qb = $this->createQueryBuilder('r');
        
        $qb->select('count(r.id)')
            ->where('r.reportedBy = :user')
            ->andWhere('r.reportedPost = :post')
            ->setParameter('user', $user)
            ->setParameter('post', $post);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function hasUserReportedComment(User $user, Comment $comment): bool
    {
        $qb = $this->createQueryBuilder('r');

        $qb->select('count(r.id)')
            ->where('r.reportedBy = :user')
            ->andWhere('r.reportedComment = :comment')
            ->setParameter('user', $user)
            ->setParameter('comment', $comment);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function hasUserReportedUser(User $user, User $reportedUser): bool
    {
        $qb = $this->createQueryBuilder('r');
        
        $qb->select('count(r.id)')
            ->where('r.reportedBy = :user')
            ->andWhere('r.reportedUser = :reportedUser')
            ->setParameter('user', $user)
            ->setParameter('reportedUser', $reportedUser);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
    //    /**
    //     * @return Report[] Returns an array of Report objects
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

    //    public function findOneBySomeField($value): ?Report
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
