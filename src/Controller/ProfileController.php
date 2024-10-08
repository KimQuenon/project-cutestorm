<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
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
    /**
     * Feed (posts by followed users)
     *
     * @param integer $page
     * @param Request $request
     * @param PostRepository $postRepo
     * @param UserRepository $userRepo
     * @param LikeRepository $likeRepo
     * @param ReportRepository $reportRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/feed/{page<\d+>?1}', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(
        int $page,
        Request $request,
        PostRepository $postRepo,
        UserRepository $userRepo,
        LikeRepository $likeRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService
    ): Response {
        $user = $this->getUser();

        $posts = $postRepo->findPostsByFollowedUsers($user);

        $followers = $userRepo->findFollowers($user);
        $followings = $userRepo->findFollowings($user);

        return $this->renderProfilePage(
            $user,
            $posts,
            $page,
            $followers,
            $followings,
            $likeRepo,
            $reportRepo,
            $paginationService,
            $postRepo,
            $userRepo,
            'profile/feed.html.twig',
        );
    }

    /**
     * display user's posts
     *
     * @param integer $page
     * @param Request $request
     * @param PostRepository $postRepo
     * @param UserRepository $userRepo
     * @param LikeRepository $likeRepo
     * @param ReportRepository $reportRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/profile/posts/{page<\d+>?1}', name: 'profile_posts')]
    #[IsGranted('ROLE_USER')]
    public function userPosts(
        int $page,
        Request $request,
        PostRepository $postRepo,
        UserRepository $userRepo,
        LikeRepository $likeRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService
    ): Response {
        $user = $this->getUser();

        // Fetch posts by the logged-in user
        $posts = $postRepo->sortPostsByUser($user);

        $followers = $userRepo->findFollowers($user);
        $followings = $userRepo->findFollowings($user);

        // Render the profile page with user's own posts
        return $this->renderProfilePage(
            $user,
            $posts,
            $page,
            $followers,
            $followings,
            $likeRepo,
            $reportRepo,
            $paginationService,
            $postRepo,
            $userRepo,
            'profile/posts.html.twig'
        );
    }


    /**
     * display user's profile + posts if not private
     */
    #[Route('/profile/{slug}/{page<\d+>?1}', name: 'profile_show')]
    public function viewProfile(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $profileUser,
        int $page,
        PostRepository $postRepo,
        UserRepository $userRepo,
        LikeRepository $likeRepo,
        FollowingRepository $followingRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        $isPrivate = $profileUser->isPrivate() && $user !== $profileUser;
        $isFollowing = !$isPrivate || ($user && $followingRepo->isFollowing($user, $profileUser));

        $posts = !$isPrivate || $isFollowing ? $postRepo->sortPostsByUser($profileUser) : [];

        $hasReportedProfile = $user ? $reportRepo->hasUserReportedUser($user, $profileUser) : false;

        $followers = $userRepo->findFollowers($profileUser);
        $followings = $userRepo->findFollowings($profileUser);

        return $this->renderProfilePage(
            $user,
            $posts,
            $page,
            $followers,
            $followings,
            $likeRepo,
            $reportRepo,
            $paginationService,
            $postRepo,
            $userRepo,
            'profile/show.html.twig',
            $profileUser,
            $isPrivate,
            $isFollowing,
            $hasReportedProfile,
        );
    }

    /**
     * Mutual fonctions
     *
     * @param User|null $user
     * @param array $posts
     * @param integer $page
     * @param array $followers
     * @param array $followings
     * @param LikeRepository $likeRepo
     * @param ReportRepository $reportRepo
     * @param PaginationService $paginationService
     * @param PostRepository $postRepo
     * @param UserRepository $userRepo
     * @param string $template
     * @param User|null $profileUser
     * @param boolean $isPrivate
     * @param boolean $isFollowing
     * @param boolean $hasReportedProfile
     * @param string|null $podiumPosition
     * @return Response
     */
    private function renderProfilePage(
        ?User $user, // Accept null for cases where user is not authenticated
        array $posts,
        int $page,
        array $followers,
        array $followings,
        LikeRepository $likeRepo,
        ReportRepository $reportRepo,
        PaginationService $paginationService,
        PostRepository $postRepo,
        UserRepository $userRepo,
        string $template,
        User $profileUser = null,
        bool $isPrivate = false,
        bool $isFollowing = true,
        bool $hasReportedProfile = false,
        ?string $podiumPosition = null
    ): Response {
        $itemsPerPage = 9;

        // Pagination logic
        $pagination = $paginationService->paginate($posts, $page, $itemsPerPage);
        $paginatedPosts = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        // Obtain liked post slugs
        $likedPostSlugs = $user ? $this->getLikedPostSlugs($user, $likeRepo) : [];

        // Obtain reported post IDs
        $reportedPostIds = $user ? $this->getReportedPostIds($user, $paginatedPosts, $reportRepo) : [];

        $mostLikedPost = $this->getPodiumPosition($profileUser ?? $user, $postRepo->findTopLikedPosts(), 'post');
        $mostCommentedPost = $this->getPodiumPosition($profileUser ?? $user, $postRepo->findTopCommentedPosts(), 'comment');
        $mostLikedUser = $this->getPodiumPosition($profileUser ?? $user, $userRepo->findTopLikedUsers(), 'user');
        $mostActiveUser = $this->getPodiumPosition($profileUser ?? $user, $userRepo->findTopCreators(), 'creator');
        $mostFollowedUser = $this->getPodiumPosition($profileUser ?? $user, $userRepo->findTopFollowedUsers(), 'popular');

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
            'mostLikedPost' => $mostLikedPost,
            'mostCommentedPost' => $mostCommentedPost,
            'mostLikedUser' => $mostLikedUser,
            'mostActiveUser' => $mostActiveUser,
            'mostFollowedUser'=> $mostFollowedUser,
            'followers' => $followers,
            'followings' => $followings,
        ]);
    }

    /**
     * Likes
     *
     * @param User $user
     * @param LikeRepository $likeRepo
     * @return array
     */
    private function getLikedPostSlugs(User $user, LikeRepository $likeRepo): array
    {
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        return array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
    }

    /**
     * Reporting profiles
     *
     * @param User $user
     * @param array $posts
     * @param ReportRepository $reportRepo
     * @return array
     */
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

    /**
     * display badges (awards)
     *
     * @param User $user
     * @param array $items
     * @param string $type
     * @return string|null
     */
    private function getPodiumPosition(User $user, array $items, string $type): ?string
    {
        foreach ($items as $index => $item) {
            if ($type === 'post' && $item->getAuthor() === $user) {
                return $this->getPodiumRank($index);
            }
            if ($type === 'comment' && $item->getAuthor() === $user) {
                return $this->getPodiumRank($index);
            }
            if ($type === 'user' && $item['id'] === $user->getId()) {
                return $this->getPodiumRank($index);
            }
            if ($type === 'creator' && $item['id'] === $user->getId()) {
                return $this->getPodiumRank($index);
            }
            if ($type === 'popular' && $item['id'] === $user->getId()) {
                return $this->getPodiumRank($index);
            }
        }
        return null;
    }

    private function getPodiumRank(int $index): ?string
    {
        return match ($index) {
            0 => 'gold',
            1 => 'silver',
            2 => 'bronze',
            default => null
        };
    }

}
