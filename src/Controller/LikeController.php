<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\LikeComment;
use App\Repository\LikeRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LikeCommentRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LikeController extends AbstractController
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    #[Route('/posts/{slug}/like', name: 'post_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addLike(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, EntityManagerInterface $manager, LikeRepository $likeRepo, NotificationRepository $notificationRepo): JsonResponse
    {
        $user = $this->getUser();

        if ($post->getAuthor() === $user) {
            return new JsonResponse(['error' => 'Cannot like your own post'], Response::HTTP_FORBIDDEN);
        }

        $existingLike = $likeRepo->findOneBy(['post' => $post, 'user' => $user]);

        if ($existingLike) {
            // If the user has already liked the post, remove the like
            $manager->remove($existingLike);

            $notification = $notificationRepo->findOneBy([
                'type' => 'like',
                'user' => $user,
                'relatedUser' => $post->getAuthor(),
                'post' => $post,
            ]);

            $manager->remove($notification);

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

            $this->notificationService->addNotification('like', $user, $post->getAuthor(), $post, null);

        }

        // Get the updated like count
        $likeCount = $post->getLikes()->count();

        // Return a JSON response
        return new JsonResponse(['likeCount' => $likeCount, 'liked' => $liked]);
    }

    
    #[Route('/comments/{id}/like', name: 'comment_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addLikeComment(Comment $comment, EntityManagerInterface $manager, LikeCommentRepository $likeCommentRepo, NotificationRepository $notificationRepo): JsonResponse
    {
        $user = $this->getUser();
    
        if ($comment->getAuthor() === $user) {
            return new JsonResponse(['error' => 'Cannot like your own comment'], Response::HTTP_FORBIDDEN);
        }
    
        $existingLike = $likeCommentRepo->findOneBy(['comment' => $comment, 'user' => $user]);
    
        if ($existingLike) {
            // If the user has already liked the comment, remove the like
            $manager->remove($existingLike);

            $notification = $notificationRepo->findOneBy([
                'type' => 'likeComment',
                'user' => $user,
                'relatedUser' => $comment->getAuthor(),
                'post' => $comment->getPost(),
                'comment' => $comment
            ]);

            $manager->remove($notification);

            $manager->flush();
            $liked = false;
        } else {
            // Otherwise, create a new like
            $likeComment = new LikeComment();
            $likeComment->setComment($comment);
            $likeComment->setUser($user);
            $manager->persist($likeComment);
            $manager->flush();
            $liked = true;
    
            // Optionally, add a notification
            $this->notificationService->addNotification('likeComment', $user, $comment->getAuthor(), $comment->getPost(), $comment);
        }
    
        // Get the updated like count
        $likeCount = $comment->getLikeComments()->count();
    
        // Return a JSON response
        return new JsonResponse(['likeCount' => $likeCount, 'liked' => $liked]);
    }
    
}
