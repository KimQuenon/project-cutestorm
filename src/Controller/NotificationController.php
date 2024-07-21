<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

        // Récupérer les notifications pour ces posts
        $notifications = $notificationRepo->findByPosts($posts);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications
        ]);
    }
}
