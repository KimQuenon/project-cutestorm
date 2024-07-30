<?php

namespace App\Repository;

use App\Entity\User;
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
    
    public function findPendingRequests(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isAccepted = false') // Only pending requests
            ->andWhere('c.recipient = :user') // Pending requests for the logged-in user
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
