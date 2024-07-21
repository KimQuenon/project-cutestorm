<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/feed', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/feed.html.twig', [
            'user'=>$user,
        ]);
    }

    #[Route('/profile/{slug}', name: 'profile_show')]
    public function viewProfile(#[MapEntity(mapping: ['slug' => 'slug'])] User $profileUser, PostRepository $postRepo): Response
    {
        $posts = $postRepo->sortPostsByUser($profileUser);

        $user = $this->getUser();

        return $this->render('profile/show.html.twig', [
            'profileUser'=>$profileUser,
            'user'=>$user,
            'posts'=>$posts
        ]);
    }
}
