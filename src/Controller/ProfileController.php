<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Service\PaginationService;
use App\Repository\ReportRepository;
use App\Repository\FollowingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/feed/{page<\d+>?1}', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(int $page, Request $request, PostRepository $postRepo, LikeRepository $likeRepo, ReportRepository $reportRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
        $currentPage = $page;
        $itemsPerPage = 2;

        // Obtenir les posts des utilisateurs suivis
        $posts = $postRepo->findPostsByFollowedUsers($user);

        // Pagination des posts
        $pagination = $paginationService->paginate($posts, $currentPage, $itemsPerPage);
        $postsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        // Obtenir les slugs des posts que l'utilisateur a aimés
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(function($like) {
            return $like->getPost()->getSlug();
        }, $likedPosts);

        $reportedPostIds = [];
        if ($user) {
            foreach ($postsPaginated as $post) {
                if ($reportRepo->hasUserReportedPost($user, $post)) {
                    $reportedPostIds[] = $post->getId();
                }
            }
        }

        return $this->render('profile/feed.html.twig', [
            'user' => $user,
            'posts' => $postsPaginated,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'currentPage' => $currentPage, // Assurez-vous que c'est bien la variable que vous utilisez dans Twig
            'totalPages' => $totalPages,   // Utilisez la même variable dans Twig
        ]);
    }

    #[Route('/profile/{slug}/{page<\d+>?1}', name: 'profile_show')]
    public function viewProfile(#[MapEntity(mapping: ['slug' => 'slug'])] User $profileUser, int $page, PostRepository $postRepo, LikeRepository $likeRepo, FollowingRepository $followingRepo, ReportRepository $reportRepo, PaginationService $paginationService): Response
    {
        $user = $this->getUser();
    
        // Determine if the profile is private and if the user is not the profile owner
        $isPrivate = $profileUser->isPrivate() && $user !== $profileUser;
        $isFollowing = !$isPrivate || $followingRepo->isFollowing($user, $profileUser);
    
        // Fetch posts and liked posts based on visibility conditions
        $posts = !$isPrivate || $isFollowing ? $postRepo->sortPostsByUser($profileUser) : [];
        
        $currentPage = $page;
        $itemsPerPage = 2;

        // Use the pagination service to get paginated results
        $pagination = $paginationService->paginate($posts, $currentPage, $itemsPerPage);
        $paginatedPosts = $pagination['items'];
        $totalPages = $pagination['totalPages'];


        $likedPostSlugs = [];
        if (!$isPrivate || $isFollowing) {
            $likedPosts = $likeRepo->findBy(['user' => $user]);
            $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
        }

        $reportedPostIds = [];
        if ($user) {
            foreach ($profileUser->getPosts() as $post) {
                if ($reportRepo->hasUserReportedPost($user, $post)) {
                    $reportedPostIds[] = $post->getId();
                }
            }
        }
    
        $hasReportedProfile = $user ? $reportRepo->hasUserReportedUser($user, $profileUser) : false;

        return $this->render('profile/show.html.twig', [
            'profileUser' => $profileUser,
            'user' => $user,
            'posts' => $paginatedPosts,
            'likedPostSlugs' => $likedPostSlugs,
            'isPrivate' => $isPrivate,
            'isFollowing' => $isFollowing,
            'reportedPostIds' => $reportedPostIds,
            'hasReportedProfile' => $hasReportedProfile,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages, 
        ]);
    }
    
}
