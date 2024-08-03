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
            ->select('u.id, u.pseudo, u.slug, COUNT(l.id) as total_likes')
            ->where('u.isPrivate = false')
            ->groupBy('u.id, u.pseudo') // Vous devez grouper par tous les champs sélectionnés non agrégés
            ->orderBy('total_likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult(); // Utilisez getArrayResult pour obtenir des résultats scalaires
    }
    
    public function findTopCreators(int $limit = 3): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.posts', 'p')
            ->select('u.id, u.pseudo, u.slug, COUNT(p.id) as post_count')
            ->where('u.isPrivate = false') // Ensure users are not private
            ->groupBy('u.id, u.pseudo, u.slug') // Group by all selected fields that are not aggregated
            ->orderBy('post_count', 'DESC') // Order by the number of posts in descending order
            ->setMaxResults($limit) // Limit the number of results
            ->getQuery()
            ->getArrayResult(); // Use getArrayResult to get scalar results
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
