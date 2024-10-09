<?php

namespace App\Repository;

use App\Entity\Like;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function sortPostsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.author = :user')
            ->setParameter('user', $user)
            ->orderBy('p.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findPostsByFollowedUsers(User $user): array
    {
        // Récupérer les utilisateurs suivis par l'utilisateur
        $followedUsers = $user->getFollowings()->map(fn($f) => $f->getFollowedUser())->toArray();
    
        // Créer la requête principale pour trouver les posts des utilisateurs suivis
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->where('p.author IN (:followedUsers)')
            ->setParameter('followedUsers', $followedUsers)
            ->andWhere('p.id NOT IN (:likedPosts)')
            ->setParameter('likedPosts', $this->getLikedPostIds($user))
            ->orderBy('p.id', 'DESC')
            ->getQuery();
    
        return $queryBuilder->getResult();
    }
    
    
    private function getLikedPostIds(User $user): array
    {
        $likedPosts = $this->createQueryBuilder('p')
            ->select('IDENTITY(l.post)')
            ->leftJoin('p.likes', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();
    
        // Convertir le tableau de résultats scalaires en tableau d'identifiants de posts
        return array_column($likedPosts, 1); // Si le résultat est un tableau de tableaux
    }
    

    //most liked posts
    public function findTopLikedPosts(int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->innerJoin('p.author', 'a')
            ->where('a.isPrivate = false')
            ->groupBy('p.id')
            ->orderBy('COUNT(l.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // most commented post
    public function findTopCommentedPosts(int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->innerJoin('p.author', 'a')
            ->where('a.isPrivate = false')
            ->groupBy('p.id')
            ->orderBy('COUNT(c.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // liked by users
    public function findLikedPostsByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.likes', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.id', 'DESC'); // Optionally sort by like date

        // Apply the limit if provided
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    //for searchbar results
    public function findByTitleOrPseudoQuery(string $term): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a') // Assuming 'author' is the property name for the user relationship
            ->where('p.title LIKE :term OR a.pseudo LIKE :term')
            ->setParameter('term', '%' . $term . '%');
    }


    //    /**
    //     * @return Post[] Returns an array of Post objects
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

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
