<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AwardController extends AbstractController
{
    /**
     * list of awards
     *
     * @param PostRepository $postRepo
     * @param UserRepository $userRepo
     * @return Response
     */
    #[Route('/awards', name: 'awards')]
    public function index(PostRepository $postRepo, UserRepository $userRepo): Response
    {
        $topLikedPosts = $postRepo->findTopLikedPosts();
        $topCommentedPosts = $postRepo->findTopCommentedPosts();
        $topLikedUsers = $userRepo->findTopLikedUsers();
        $topCreators = $userRepo->findTopCreators();
        $topFollowedUsers = $userRepo->findTopFollowedUsers();

        return $this->render('awards.html.twig', [
            'topLikedPosts' => $topLikedPosts,
            'topCommentedPosts' => $topCommentedPosts,
            'topLikedUsers' => $topLikedUsers,
            'topCreators' => $topCreators,
            'topFollowedUsers' => $topFollowedUsers
        ]);
    }
}
