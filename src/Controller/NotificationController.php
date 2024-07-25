<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notifications_index')]
    #[IsGranted('ROLE_USER')]
    public function index(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        
        // Récupérer tous les posts de l'utilisateur
        $posts = $postRepo->findBy(['author' => $user]);
        
        // Récupérer les notifications associées aux posts et celles où l'utilisateur est relatedUser
        $notifications = $notificationRepo->getAllNotifications($user);

        $unreadCount = $notificationRepo->countUnreadNotifications($user);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/notifications/likes', name: 'notifications_likes')]
    #[IsGranted('ROLE_USER')]
    public function likes(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findBy(['author' => $user]);
        $notifications = $notificationRepo->getLikesNotifications($posts, 'like');
        $unreadCount = $notificationRepo->countUnreadLikesNotifications($user, $posts);

        return $this->render('notifications/likes.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/notifications/follows', name: 'notifications_follows')]
    #[IsGranted('ROLE_USER')]
    public function follows(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepo->getFollowsNotifications($user);
        $unreadCount = $notificationRepo->countUnreadFollowsNotifications($user);

        return $this->render('notifications/follows.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/notifications/mark-read', name: 'mark_notifications_read')]
    #[IsGranted('ROLE_USER')]
    public function markRead(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        $notificationRepo->markAllNotificationsAsRead($user);

        return $this->redirectToRoute('notifications_index');
    }

    #[Route('/notifications/mark-likes-read', name: 'mark_likes_read')]
    #[IsGranted('ROLE_USER')]
    public function markLikesRead(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        $notificationRepo->markLikesAsRead($user);

        return $this->redirectToRoute('notifications_likes');
    }

    #[Route('/notifications/mark-follows-read', name: 'mark_follows_read')]
    #[IsGranted('ROLE_USER')]
    public function markFollowsRead(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        
        $notificationRepo->markFollowsAsRead($user);

        return $this->redirectToRoute('notifications_follows');
    }
}