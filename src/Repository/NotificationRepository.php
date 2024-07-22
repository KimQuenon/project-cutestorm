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

    public function markAllNotificationsAsRead($user, array $posts)
    {
        // Extraire les IDs des posts si $posts est un tableau d'objets Post
        $postIds = array_map(fn($post) => $post->getId(), $posts); 
    
        // Mettre à jour les notifications liées aux posts de l'utilisateur connecté
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.post IN (:posts)')
            ->andWhere('n.isRead = :isNotRead')
            ->setParameter('isRead', true)
            ->setParameter('posts', $postIds)
            ->setParameter('isNotRead', false)
            ->getQuery()
            ->execute();
    
        // Mettre à jour les notifications où l'utilisateur connecté est le relatedUser
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.relatedUser = :user')
            ->andWhere('n.isRead = :isNotRead')
            ->setParameter('isRead', true)
            ->setParameter('user', $user)
            ->setParameter('isNotRead', false)
            ->getQuery()
            ->execute();
    }
    

    public function markLikesAsRead($user, array $posts)
    {
        // Assure-toi que les posts contiennent les objets ou les IDs valides
        $postIds = array_map(fn($post) => $post->getId(), $posts); // Extraire les IDs des posts si ce sont des objets
    
        // Construire la requête pour mettre à jour les notifications
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.type = :type') // Filtrer par type de notification (likes dans ce cas)
            ->andWhere('n.post IN (:posts)') // Filtrer par les posts spécifiés
            ->andWhere('n.isRead = :isNotRead') // Filtrer les notifications non lues
            ->setParameter('isRead', true) // Valeur pour marquer comme lu
            ->setParameter('type', 'like') // Type de notification
            ->setParameter('posts', $postIds) // Liste des IDs des posts
            ->setParameter('isNotRead', false) // Filtrer les notifications qui ne sont pas encore lues
            ->getQuery()
            ->execute(); // Exécution de la requête
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
