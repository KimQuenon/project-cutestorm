<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addNotification(string $type, User $user, ?User $relatedUser, ?Post $post = null, ?Comment $comment = null): void
    {
        $notification = new Notification();
        $notification->setType($type)
                     ->setUser($user)
                     ->setPost($type === 'like' || $type === 'comment' ? $post : null)
                     ->setComment($type === 'comment' ? $comment : null)
                     ->setRelatedUser($relatedUser)
                     ->setRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
