<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AwardController extends AbstractController
{
    #[Route('/awards', name: 'awards_index')]
    public function index(PostRepository $postRepo, UserRepository $userRepo): Response
    {
        $topLikedPosts = $postRepo->findTopLikedPosts();
        $topCommentedPosts = $postRepo->findTopCommentedPosts();
        $topLikedUsers = $userRepo->findTopLikedUsers();
        $topCreators = $userRepo->findTopCreators();

        return $this->render('awards/index.html.twig', [
            'topLikedPosts' => $topLikedPosts,
            'topCommentedPosts' => $topCommentedPosts,
            'topLikedUsers' => $topLikedUsers,
            'topCreators' => $topCreators
        ]);
    }
}
