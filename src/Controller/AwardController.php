<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AwardController extends AbstractController
{
    #[Route('/awards', name: 'awards_index')]
    public function index(PostRepository $postRepo): Response
    {
        $topLikedPosts = $postRepo->findTopLikedPosts();
        return $this->render('awards/index.html.twig', [
            'topLikedPosts' => $topLikedPosts,
        ]);
    }
}
