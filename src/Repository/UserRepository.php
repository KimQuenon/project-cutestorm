<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findTopLikedUsers(int $limit = 3): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.posts', 'p')
            ->leftJoin('p.likes', 'l')
            ->select('u.id, u.pseudo, u.slug, u.bio, u.avatar, COUNT(l.id) as total_likes')
            ->where('u.isPrivate = false')
            ->groupBy('u.id')
            ->orderBy('total_likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
    
    public function findTopCreators(int $limit = 3): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.posts', 'p')
            ->select('u.id, u.pseudo, u.slug, u.bio, u.avatar, COUNT(p.id) as post_count')
            ->where('u.isPrivate = false')
            ->groupBy('u.id')
            ->orderBy('post_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findTopFollowedUsers(int $limit = 3): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.followedByUsers', 'f')
            ->select('u.id, u.pseudo, u.slug, u.bio, u.avatar, COUNT(f.id) as follower_count')
            ->where('u.isPrivate = false')
            ->groupBy('u.id')
            ->orderBy('follower_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }


    public function findFollowers(User $user, $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.followings', 'f')
            ->where('f.followedUser = :user')
            ->setParameter('user', $user)
            ->orderBy('f.id', 'DESC'); // Assurez-vous d'avoir un champ de date pour le tri
        
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    
    
    public function findFollowings(User $user, $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.followedByUsers', 'f')
            ->where('f.followerUser = :user')
            ->setParameter('user', $user)
            ->orderBy('f.id', 'DESC'); // Assurez-vous d'avoir un champ de date pour le tri
    
        if ($limit) {
            $qb->setMaxResults($limit);
        }
    
        return $qb->getQuery()->getResult();
    }
    
    

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
