<?php
namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function getAllNotifications($user, $limit = null)
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.relatedUser = :user')
            ->setParameter('user', $user)
            ->orderBy('n.id', 'DESC');
    
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
    
        return $qb->getQuery()->getResult();
    }
    

    public function getLikesNotifications(array $posts, array $comments)
    {
        return $this->createQueryBuilder('n')
            ->where('n.post IN (:posts) AND n.type = :likeType')
            ->orWhere('n.comment IN (:comments) AND n.type = :commentType')
            ->setParameter('posts', $posts)
            ->setParameter('comments', $comments)
            ->setParameter('likeType', 'like')
            ->setParameter('commentType', 'likeComment')
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getFollowsNotifications($user)
    {
        return $this->createQueryBuilder('n')
        ->where('n.relatedUser = :user')
        ->andWhere('n.type IN (:types)')
        ->setParameter('user', $user)
        ->setParameter('types', ['follow', 'request'])
        ->orderBy('n.id', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function getCommentsNotifications($user)
    {
        return $this->createQueryBuilder('n')
        ->where('n.relatedUser = :user')
        ->andWhere('n.type IN (:types)')
        ->setParameter('user', $user)
        ->setParameter('types', ['comment', 'reply'])
        ->orderBy('n.id', 'DESC')
        ->getQuery()
        ->getResult();
    }

    public function countUnreadNotifications($user)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.relatedUser = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadLikesNotifications($user, array $posts, array $comments)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where(
                'n.post IN (:posts) AND n.type = :likeType'
            )
            ->orWhere(
                'n.comment IN (:comments) AND n.type = :commentType'
            )
            ->andWhere('n.isRead = false')
            ->setParameter('posts', $posts)
            ->setParameter('comments', $comments)
            ->setParameter('likeType', 'like')
            ->setParameter('commentType', 'likeComment')
            ->getQuery()
            ->getSingleScalarResult();
    }
    

    public function countUnreadFollowsNotifications($user)
    {
        return $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->where('n.relatedUser = :user')
        ->andWhere('n.type IN (:types)')
        ->andWhere('n.isRead = false')
        ->setParameter('user', $user)
        ->setParameter('types', ['follow', 'request'])
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function countUnreadCommentsNotifications($user)
    {
        return $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->where('n.relatedUser = :user')
        ->andWhere('n.type IN (:types)')
        ->andWhere('n.isRead = false')
        ->setParameter('user', $user)
        ->setParameter('types', ['comment', 'reply'])
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function markAllNotificationsAsRead($user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.relatedUser = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function markLikesAsRead($user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where(
                'n.relatedUser = :user AND (n.type = :likeType OR n.type = :commentType)'
            )
            ->setParameter('user', $user)
            ->setParameter('likeType', 'like')
            ->setParameter('commentType', 'likeComment')
            ->getQuery()
            ->execute();
    }    

    public function markFollowsAsRead($user)
    {
        $this->createQueryBuilder('n')
        ->update()
        ->set('n.isRead', ':isRead')
        ->where('n.relatedUser = :user')
        ->andWhere('n.type IN (:types)')
        ->setParameter('user', $user)
        ->setParameter('types', ['follow', 'request'])
        ->setParameter('isRead', true)
        ->getQuery()
        ->execute();
    }

    public function markCommentsAsRead($user)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.relatedUser = :user')
            ->andWhere('n.type IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('types', ['comment', 'reply'])
            ->setParameter('isRead', true)
            ->getQuery()
            ->execute();
    }
}
