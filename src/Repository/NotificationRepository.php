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

    public function getAllNotifications($user, array $posts)
    {
        return $this->createQueryBuilder('n')
            ->where('n.post IN (:posts)')
            ->orWhere('n.relatedUser = :user')
            ->setParameter('posts', $posts)
            ->setParameter('user', $user)
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getLikesNotifications(array $posts, string $type)
    {
        return $this->createQueryBuilder('n')
            ->where('n.post IN (:posts)')
            ->andWhere('n.type = :type')
            ->setParameter('posts', $posts)
            ->setParameter('type', $type)
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getFollowsNotifications($user)
    {
        return $this->createQueryBuilder('n')
            ->where('n.relatedUser = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'follow')
            ->orderBy('n.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadNotifications($user, array $posts)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.post IN (:posts)')
            ->orWhere('n.relatedUser = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('posts', $posts)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadLikesNotifications($user, array $posts)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.post IN (:posts)')
            ->andWhere('n.type = :type')
            ->andWhere('n.isRead = false')
            ->setParameter('posts', $posts)
            ->setParameter('type', 'like')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadFollowsNotifications($user)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.relatedUser = :user')
            ->andWhere('n.type = :type')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->setParameter('type', 'follow')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllNotificationsAsRead($user)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function markLikesAsRead($user)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.user = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'like')
            ->getQuery()
            ->execute();
    }

    public function markFollowsAsRead($user)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.relatedUser = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'follow')
            ->getQuery()
            ->execute();
    }
}
