<?php

namespace App\Controller;

use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\ReportRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommunityController extends AbstractController
{
    #[Route('/community', name: 'community')]
    public function index(PostRepository $postRepo, LikeRepository $likeRepo, ReportRepository $reportRepo, UserRepository $userRepo): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findTopLikedPosts(6);
        $topLikedUsers = $userRepo->findTopLikedUsers();

        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);

        $reportedPostIds = [];
        if ($user) {
            foreach ($posts as $post) {
                if ($reportRepo->hasUserReportedPost($user, $post)) {
                    $reportedPostIds[] = $post->getId();
                }
            }
        }

        return $this->render('community.html.twig', [
            'posts' => $posts,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'topLikedUsers' => $topLikedUsers
        ]);
    }
}
