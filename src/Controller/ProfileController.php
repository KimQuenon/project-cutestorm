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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/feed/{page<\d+>?1}', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(
        int $page,
        Request $request,
        PostRepository $postRepo,
        LikeRepository $likeRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService
    ): Response {
        /** @var User $user */
        $user = $this->getUser(); // Ensure user is always of type User

        // Get posts from followed users
        $posts = $postRepo->findPostsByFollowedUsers($user);

        // Render the feed page
        return $this->renderProfilePage(
            $user,
            $posts,
            $page,
            $likeRepo,
            $reportRepo,
            $paginationService,
            $postRepo,
            'profile/feed.html.twig',
        );
    }

    #[Route('/profile/{slug}/{page<\d+>?1}', name: 'profile_show')]
    public function viewProfile(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $profileUser,
        int $page,
        PostRepository $postRepo,
        LikeRepository $likeRepo,
        FollowingRepository $followingRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        // Determine if the profile is private and if the user is not the profile owner
        $isPrivate = $profileUser->isPrivate() && $user !== $profileUser;
        $isFollowing = !$isPrivate || ($user && $followingRepo->isFollowing($user, $profileUser));

        // Fetch posts based on visibility conditions
        $posts = !$isPrivate || $isFollowing ? $postRepo->sortPostsByUser($profileUser) : [];

        $hasReportedProfile = $user ? $reportRepo->hasUserReportedUser($user, $profileUser) : false;

        // Render the profile page
        return $this->renderProfilePage(
            $user, // User can be null here, handle in renderProfilePage
            $posts,
            $page,
            $likeRepo,
            $reportRepo,
            $paginationService,
            $postRepo,
            'profile/show.html.twig',
            $profileUser,
            $isPrivate,
            $isFollowing,
            $hasReportedProfile,
        );
    }

    private function renderProfilePage(
        ?User $user, // Accept null for cases where user is not authenticated
        array $posts,
        int $page,
        LikeRepository $likeRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService,
        PostRepository $postRepo,
        string $template,
        User $profileUser = null,
        bool $isPrivate = false,
        bool $isFollowing = true,
        bool $hasReportedProfile = false,
        ?string $podiumPosition = null
    ): Response {
        $itemsPerPage = 2;

        // Pagination logic
        $pagination = $paginationService->paginate($posts, $page, $itemsPerPage);
        $paginatedPosts = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        // Obtain liked post slugs
        $likedPostSlugs = $user ? $this->getLikedPostSlugs($user, $likeRepo) : [];

        // Obtain reported post IDs
        $reportedPostIds = $user ? $this->getReportedPostIds($user, $paginatedPosts, $reportRepo) : [];

        $topLikedPosts = $postRepo->findTopLikedPosts();
        $podiumPosition = $this->getPodiumPosition($profileUser ?? $user, $topLikedPosts);

        // Render the template
        return $this->render($template, [
            'user' => $user,
            'profileUser' => $profileUser,
            'posts' => $paginatedPosts,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'isPrivate' => $isPrivate,
            'isFollowing' => $isFollowing,
            'hasReportedProfile' => $hasReportedProfile,
            'podiumPosition' => $podiumPosition
        ]);
    }

    private function getLikedPostSlugs(User $user, LikeRepository $likeRepo): array
    {
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        return array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
    }

    private function getReportedPostIds(User $user, array $posts, ReportRepository $reportRepo): array
    {
        $reportedPostIds = [];
        foreach ($posts as $post) {
            if ($reportRepo->hasUserReportedPost($user, $post)) {
                $reportedPostIds[] = $post->getId();
            }
        }
        return $reportedPostIds;
    }

    private function getPodiumPosition(User $user, array $topLikedPosts): ?string
    {
        $postIds = array_map(fn($post) => $post->getId(), $topLikedPosts);
        
        foreach ($topLikedPosts as $index => $post) {
            if ($post->getAuthor() === $user) {
                return match ($index) {
                    0 => 'gold',
                    1 => 'silver',
                    2 => 'bronze',
                    default => null
                };
            }
        }

        return null;
    }
}
