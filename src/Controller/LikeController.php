<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LikeController extends AbstractController
{
    #[Route('/posts/{slug}/like', name: 'post_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addLike(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, EntityManagerInterface $manager, LikeRepository $likeRepo): JsonResponse
    {
        $user = $this->getUser();
        
        if ($post->getAuthor() === $user) {
            return new JsonResponse(['error' => 'Cannot like your own post'], Response::HTTP_FORBIDDEN);
        }

        $existingLike = $likeRepo->findOneBy(['post' => $post, 'user' => $user]);

        if ($existingLike) {
            // If the user has already liked the post, remove the like
            $manager->remove($existingLike);
            $manager->flush();
            $liked = false;
        } else {
            // Otherwise, create a new like
            $like = new Like();
            $like->setPost($post);
            $like->setUser($user);
            $manager->persist($like);
            $manager->flush();
            $liked = true;
        }

        // Get the updated like count
        $likeCount = $post->getLikes()->count();

        // Return a JSON response
        return new JsonResponse(['likeCount' => $likeCount, 'liked' => $liked]);
    }
}
