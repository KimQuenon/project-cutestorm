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

    public function getAllNotifications($user)
    {
        return $this->createQueryBuilder('n')
            ->where('n.relatedUser = :user')
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
            ->where('n.relatedUser = :user')
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
