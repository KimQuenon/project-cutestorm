<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{
    #[Route('/comment/{id}/delete', name:"comment_delete")]
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
