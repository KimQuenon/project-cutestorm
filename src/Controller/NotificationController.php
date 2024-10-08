<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Service\PaginationService;
use App\Repository\CommentRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NotificationController extends AbstractController
{
    /**
     * display all notifications
     *
     * @param integer $page
     * @param NotificationRepository $notificationRepo
     * @param PostRepository $postRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('profile/notifications/{page<\d+>?1}', name: 'notifications_index')]
    #[IsGranted('ROLE_USER')]
    public function index(int $page, NotificationRepository $notificationRepo, PostRepository $postRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();

        $posts = $postRepo->findBy(['author' => $user]);
        
        $notifications = $notificationRepo->getAllNotifications($user);
        
        $unreadCount = $notificationRepo->countUnreadNotifications($user);

        $currentPage = $page;
        $itemsPerPage = 10;

        $pagination = $paginationService->paginate($notifications, $currentPage, $itemsPerPage);
        $notificationsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/notifications/index.html.twig', [
            'notifications' => $notificationsPaginated,
            'unreadCount' => $unreadCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * display notifications - like type
     *
     * @param integer $page
     * @param NotificationRepository $notificationRepo
     * @param PostRepository $postRepo
     * @param CommentRepository $commentRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/profile/notifications/likes/{page<\d+>?1}', name: 'notifications_likes')]
    #[IsGranted('ROLE_USER')]
    public function likes(int $page, NotificationRepository $notificationRepo, PostRepository $postRepo, CommentRepository $commentRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findBy(['author' => $user]);
        $comments = $commentRepo->findBy(['author' => $user]);
        $notifications = $notificationRepo->getLikesNotifications($posts, $comments);
        $unreadCount = $notificationRepo->countUnreadLikesNotifications($user, $posts, $comments);
        
        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($notifications, $currentPage, $itemsPerPage);
        $notificationsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/notifications/likes.html.twig', [
            'notifications' => $notificationsPaginated,
            'unreadCount' => $unreadCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * display notifications - user type
     *
     * @param integer $page
     * @param NotificationRepository $notificationRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('profile/notifications/follows/{page<\d+>?1}', name: 'notifications_follows')]
    #[IsGranted('ROLE_USER')]
    public function follows(int $page, NotificationRepository $notificationRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepo->getFollowsNotifications($user);
        $unreadCount = $notificationRepo->countUnreadFollowsNotifications($user);

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($notifications, $currentPage, $itemsPerPage);
        $notificationsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/notifications/follows.html.twig', [
            'notifications' => $notificationsPaginated,
            'unreadCount' => $unreadCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * display notifications - comment type
     *
     * @param integer $page
     * @param NotificationRepository $notificationRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/profile/notifications/comments/{page<\d+>?1}', name: 'notifications_comments')]
    #[IsGranted('ROLE_USER')]
    public function comments(int $page, NotificationRepository $notificationRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepo->getCommentsNotifications($user);
        $unreadCount = $notificationRepo->countUnreadCommentsNotifications($user);

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($notifications, $currentPage, $itemsPerPage);
        $notificationsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/notifications/comments.html.twig', [
            'notifications' => $notificationsPaginated,
            'unreadCount' => $unreadCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * mark all notifications as read
     *
     * @param NotificationRepository $notificationRepo
     * @param PostRepository $postRepo
     * @return Response
     */
    #[Route('/notifications/mark-read', name: 'mark_notifications_read')]
    #[IsGranted('ROLE_USER')]
    public function markRead(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        $notificationRepo->markAllNotificationsAsRead($user);

        return $this->redirectToRoute('notifications_index');
    }

    /**
     * mark all notifications as read - like type
     *
     * @param NotificationRepository $notificationRepo
     * @param PostRepository $postRepo
     * @return Response
     */
    #[Route('/notifications/mark-likes-read', name: 'mark_likes_read')]
    #[IsGranted('ROLE_USER')]
    public function markLikesRead(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        $notificationRepo->markLikesAsRead($user);

        return $this->redirectToRoute('notifications_likes');
    }

    /**
     * mark all notifications as read - user type
     *
     * @param NotificationRepository $notificationRepo
     * @return Response
     */
    #[Route('/notifications/mark-follows-read', name: 'mark_follows_read')]
    #[IsGranted('ROLE_USER')]
    public function markFollowsRead(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        
        $notificationRepo->markFollowsAsRead($user);

        return $this->redirectToRoute('notifications_follows');
    }

    /**
     * mark all notifications as read - comment type
     *
     * @param NotificationRepository $notificationRepo
     * @return Response
     */
    #[Route('/notifications/mark-comments-read', name: 'mark_comments_read')]
    #[IsGranted('ROLE_USER')]
    public function markCommentsRead(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        
        $notificationRepo->markCommentsAsRead($user);

        return $this->redirectToRoute('notifications_comments');
    }
}