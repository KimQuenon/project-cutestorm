<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    /**
     * Display all posts
     *
     * @param PostRepository $postRepo
     * @return Response
     */
    #[Route('/posts', name: 'posts_index')]
    public function index(PostRepository $postRepo): Response
    {
        $posts = $postRepo->findAll();

        return $this->render('posts/index.html.twig', [
            "posts"=> $posts
        ]);
    }


    #[Route("/posts/{slug}", name: "post_show")]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post): Response
    {
        return $this->render("posts/show.html.twig", [
            'post' => $post,
        ]);
    }
}
