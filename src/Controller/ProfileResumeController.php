<?php

namespace App\Controller;

use App\Service\SearchService;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileResumeController extends AbstractController
{   
    #[Route('/profile/resume/activities', name: 'resume_activities')]
    #[IsGranted('ROLE_USER')]
    public function index(LikeRepository $likeRepo, UserRepository $userRepo, PostRepository $postRepo, NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findLikedPostsByUser($user, 3);
        $likedPostSlugs = array_map(fn($post) => $post->getSlug(), $posts);
        $reportedPostIds = null;

        $followers = $userRepo->findFollowers($user, 9);
        $followings = $userRepo->findFollowings($user, 9);
        $notifications = $notificationRepo->getAllNotifications($user, 2);

        return $this->render('profile/resume/activities.html.twig', [
            'posts' => $posts,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'followers' => $followers,
            'followings' => $followings,
            'notifications' => $notifications
        ]);
    }

    #[Route('/profile/resume/likes/{page<\d+>?1}', name: 'resume_likes')]
    #[IsGranted('ROLE_USER')]
    public function likes(int $page, PostRepository $postRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findLikedPostsByUser($user);
        $likedPostSlugs = array_map(fn($post) => $post->getSlug(), $posts);
        $reportedPostIds = null;

        $currentPage = $page;
        $itemsPerPage = 9;

        $pagination = $paginationService->paginate($posts, $currentPage, $itemsPerPage);
        $paginatedPosts = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/resume/likes.html.twig', [
            'posts' => $paginatedPosts,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/profile/resume/followings/{page<\d+>?1}', name: 'resume_followings')]
    #[IsGranted('ROLE_USER')]
    public function followings(int $page, UserRepository $userRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $followings = $userRepo->findFollowings($user);

        $currentPage = $page;
        $itemsPerPage = 15;

        $pagination = $paginationService->paginate($followings, $currentPage, $itemsPerPage);
        $paginatedFollowings = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/resume/followings.html.twig', [
            'followings' => $paginatedFollowings,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/profile/resume/followers/{page<\d+>?1}', name: 'resume_followers')]
    #[IsGranted('ROLE_USER')]
    public function followers(int $page, UserRepository $userRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $followers = $userRepo->findFollowers($user);

        $currentPage = $page;
        $itemsPerPage = 15;

        // Use the pagination service to get paginated results
        $pagination = $paginationService->paginate($followers, $currentPage, $itemsPerPage);
        $paginatedFollowers = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('profile/resume/followers.html.twig', [
            'followers' => $paginatedFollowers,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }


}
