<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * sort by most recent messages
     *
     * @param User $user
     * @return array
     */
    public function sortConvByRecentMsg(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('MAX(m.timestamp) AS HIDDEN lastMessageTimestamp')
            ->where('c.sender = :user OR c.recipient = :user')
            ->setParameter('user', $user)
            ->groupBy('c.id')
            ->orderBy('lastMessageTimestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * find ongoing conv
     *
     * @param User $user
     * @return array
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('MAX(m.timestamp) AS HIDDEN lastMessageTimestamp')
            ->where('c.isAccepted = true') // Only accepted conversations
            ->andWhere('c.sender = :user OR c.recipient = :user')
            ->setParameter('user', $user)
            ->groupBy('c.id')
            ->orderBy('lastMessageTimestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * find conv requests
     *
     * @param User $user
     * @return array
     */
    public function findPendingRequests(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isAccepted = false')
            ->andWhere('c.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findConversationByUsers(User $recipient, User $sender): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->where('c.sender = :sender AND c.recipient = :recipient')
            ->orWhere('c.sender = :recipient AND c.recipient = :sender')
            ->setParameter('sender', $sender)
            ->setParameter('recipient', $recipient)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function replaceUserInConversations(User $user, User $anonymousUser): void
    {
        // replace user as sender
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.sender', ':anonymousUser')
            ->where('c.sender = :user')
            ->setParameter('anonymousUser', $anonymousUser)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
        
        // replace user as recipient
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.recipient', ':anonymousUser')
            ->where('c.recipient = :user')
            ->setParameter('anonymousUser', $anonymousUser)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findConversationsWithUnreadCounts(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('COUNT(CASE WHEN m.isRead = false AND m.sender != :user THEN 1 END) AS HIDDEN unreadCount')
            ->where('c.isAccepted = true') // Only accepted conversations
            ->andWhere('c.sender = :user OR c.recipient = :user')
            ->setParameter('user', $user)
            ->groupBy('c.id')
            ->orderBy('MAX(m.timestamp)', 'DESC') // Optional: order by the latest message timestamp
            ->getQuery()
            ->getResult();
    }
    
    public function countTotalUnreadMessages(User $user): int
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->select('COUNT(CASE WHEN m.isRead = false AND m.sender != :user THEN 1 END) AS totalUnread')
            ->where('c.isAccepted = true') // Only accepted conversations
            ->andWhere('c.sender = :user OR c.recipient = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
    


    //    /**
    //     * @return Conversation[] Returns an array of Conversation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conversation
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
