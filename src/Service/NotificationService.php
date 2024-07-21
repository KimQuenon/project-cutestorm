<?php

namespace App\Service;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addNotification($type, $user, $post)
    {
        $notification = new Notification();
        $notification->setType($type);
        $notification->setUser($user);
        $notification->setPost($post);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
