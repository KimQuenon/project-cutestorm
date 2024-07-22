<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/feed', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(PostRepository $postRepo, LikeRepository $likeRepo): Response
    {
        $user = $this->getUser();
    
        // Obtenir les posts des utilisateurs suivis
        $posts = $postRepo->findPostsByFollowedUsers($user);
    
        // Obtenir les slugs des posts que l'utilisateur a aimés
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(function($like) {
            return $like->getPost()->getSlug();
        }, $likedPosts);
    
        return $this->render('profile/feed.html.twig', [
            'user' => $user,
            'posts' => $posts,
            'likedPostSlugs' => $likedPostSlugs,
        ]);
    }

    #[Route('/profile/{slug}', name: 'profile_show')]
    public function viewProfile(#[MapEntity(mapping: ['slug' => 'slug'])] User $profileUser, PostRepository $postRepo, LikeRepository $likeRepo): Response
    {
        $user = $this->getUser();

        $isPrivate = $profileUser->isPrivate() && $user !== $profileUser;
        
        $posts = $isPrivate ? [] : $postRepo->sortPostsByUser($profileUser);
        $likedPostSlugs = [];

        if (!$isPrivate) {
            $likedPosts = $likeRepo->findBy(['user' => $user]);
            $likedPostSlugs = array_map(function($like) {
                return $like->getPost()->getSlug();
            }, $likedPosts);
        }

        return $this->render('profile/show.html.twig', [
            'profileUser' => $profileUser,
            'user' => $user,
            'posts' => $posts,
            'likedPostSlugs' => $likedPostSlugs,
            'isPrivate' => $isPrivate,
        ]);
    }
}
