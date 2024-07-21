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
        // Récupérer tous les posts que l'utilisateur a publiés
        $posts = $postRepo->findBy(['author' => $user]);
        $notifications = $notificationRepo->findByPosts($posts);

        // Compter les notifications non lues pour les posts de l'utilisateur
        $unreadCount = $notificationRepo->findUnreadCountByUserPosts($user);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/notifications/mark-read', name: 'notifications_mark_read')]
    #[IsGranted('ROLE_USER')]
    public function markRead(NotificationRepository $notificationRepo, PostRepository $postRepo): Response
    {
        $user = $this->getUser();
        // Récupérer tous les posts que l'utilisateur a publiés
        $posts = $postRepo->findBy(['author' => $user]);
        // Marquer les notifications liées à ces posts comme lues
        $notificationRepo->markNotificationsAsReadForPosts($posts);

        // Rediriger vers la page des notifications après la mise à jour
        return $this->redirectToRoute('notifications_index');
    }
}
