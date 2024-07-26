<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\ReplyType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{
    #[Route("/comments/reply/{id}", name: "comment_reply", methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reply(Comment $comment, Request $request, EntityManagerInterface $manager): Response {
        $user = $this->getUser();
        $post = $comment->getPost();
    
        $reply = new Comment();
        $form = $this->createForm(ReplyType::class, $reply, [
            'parent' => $comment,
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setPost($post)
                  ->setAuthor($user)
                  ->setParent($comment);
    
            $manager->persist($reply);
            $manager->flush();
    
            $this->addFlash('success', 'Reply posted');
            return $this->redirectToRoute('post_show', ['slug' => $post->getSlug()]);
        } else {
            $this->addFlash('danger', 'There was an error posting your reply.');
        }
    
        return $this->redirectToRoute('post_show', ['slug' => $post->getSlug()]);
    }
    
    #[Route('/comment/delete/{id}', name:"comment_delete")]
    #[IsGranted(
        attribute: New Expression('(user == subject and is_granted("ROLE_USER"))'),
        subject: New Expression('args["comment"].getAuthor()'),
        message: "You are not allowed to delete this comment."
    )]
    public function delete(EntityManagerInterface $manager, Comment $comment, UserRepository $userRepo): Response
    {
        $this->addFlash('danger','Comment deleted.');

        $anon = $userRepo->findOneBy(['email'=>'anon@noreply.com']);
        $post = $comment->getPost();

        $comment->setContent('This comment has been deleted.')
                ->setAuthor($anon);

        $manager->persist($comment);
        $manager->flush();

        return $this->redirectToRoute('post_show',['slug'=>$post->getSlug()]);
    }
}
