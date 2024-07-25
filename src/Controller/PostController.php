<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Entity\Comment;
use App\Entity\PostImage;
use App\Form\CommentType;
use App\Form\PostImageType;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\FollowingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    public function list(PostRepository $postRepo, LikeRepository $likeRepo, FollowingRepository $followingRepo): Response
    {
        $user = $this->getUser();
        $posts = $postRepo->findBy([], ['timestamp' => 'DESC']);
    
        // Determine which posts are visible based on author visibility and user subscription
        $visiblePosts = array_filter($posts, function ($post) use ($user, $followingRepo) {
            $author = $post->getAuthor();
            $isPrivate = $author->isPrivate();
            
            // Check if the user is following the author if the author is private
            $isFollowing = !$isPrivate || $user === $author || $followingRepo->isFollowing($user, $author);
            
            return $isFollowing;
        });
    
        // Get the posts liked by the current user
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
    
        return $this->render('posts/index.html.twig', [
            'posts' => $visiblePosts,
            'likedPostSlugs' => $likedPostSlugs,
        ]);
    }
    

    #[Route("/posts/new", name:"post_create")]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $manager): Response
    {
        $post = new Post();
        $form = $this->createform(PostType::class, $post);

        $form->handleRequest($request);

        //form complet et valid -> envoi bdd + message et redirection
        if($form->isSubmitted() && $form->IsValid())
        {
            if (count($post->getPostImages()) < 1) {
                $this->addFlash('danger', 'A post must have at least one image.');

                return $this->render("posts/new.html.twig", [
                    'myForm' => $form->createView()
                ]);
            }

            foreach ($post->getPostImages() as $image) {
                /** @var UploadedFile $file */
                $file = $image->getFile();
                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    try {
                        $file->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return $e->getMessage();
                    }

                    $image->setFilename($newFilename);
                    $image->setPost($post);
                    $manager->persist($image); 
                }
            }

            $post->setTitle(ucwords($post->getTitle()));
            $post->setAuthor($this->getUser());
            $manager->persist($post);

            $manager->flush();

            $this->addFlash(
                'success',
                "La fiche de <strong>".$post->getTitle()."</strong> a bien été enregistrée."
            );

            return $this->redirectToRoute('post_show', [
                'slug'=> $post->getSlug()
            ]);
        }

        return $this->render("posts/new.html.twig",[
            'myForm' => $form->createView()
        ]);

    }

    #[Route("/posts/{slug}", name: "post_show")]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, LikeRepository $likeRepo, FollowingRepository $followingRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        $author = $post->getAuthor();
        $isPrivate = $author->isPrivate();
        
        // Determine visibility based on author's privacy settings and user's subscription
        $canViewPost = !$isPrivate || $user === $author || $followingRepo->isFollowing($user, $author);
    
        if (!$canViewPost) {
            $this->addFlash('danger', 'This publication is private or you do not have permission to view it.');
            return $this->redirectToRoute('posts_index');
        }
        
        // Get liked posts for the current user
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);

        $comments = $post->getComments();

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->IsValid())
        {
            $comment->setPost($post)
                    ->setAuthor($user);
            $manager->persist($comment);
            $manager->flush();

            $form = $this->createForm(CommentType::class);

            $this->addFlash(
                'success',
                'Comment posted'
            );
        }
    
        return $this->render("posts/show.html.twig", [
            'post' => $post,
            'likedPostSlugs' => $likedPostSlugs,
            'comments' => $comments,
            'myForm' => $form->createView(),
        ]);
    }
    

    #[Route("/posts/{slug}/edit", name: "post_edit")]
    #[IsGranted(
        attribute: new Expression('(user === subject and is_granted("ROLE_USER")) or is_granted("ROLE_ADMIN")'),
        subject: new Expression('args["post"].getAuthor()'),
        message: "You are not allowed to delete someone else's post."
    )]
    public function edit(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(PostType::class, $post, [
            'is_edit' => true
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            // $artwork->setCoverImage($fileName);
            $manager->persist($post);

            $manager->flush();

            $this->addFlash(
            'success',
            "<strong>".$post->getTitle()."</strong> edited successfully !"
            );

            return $this->redirectToRoute('post_show',[
            'slug' => $post->getSlug()
            ]);

        }

        return $this->render("posts/edit.html.twig", [
            "post" => $post,
            "myForm"=> $form->createView()
        ]);
    }

    #[Route("/posts/{slug}/delete", name:"post_delete")]
    #[IsGranted(
        attribute: new Expression('(user === subject and is_granted("ROLE_USER")) or is_granted("ROLE_ADMIN")'),
        subject: new Expression('args["post"].getAuthor()'),
        message: "You are not allowed to delete someone else's post."
    )]
    public function delete(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, EntityManagerInterface $manager): Response
    {       
            // Supprimer toutes les images associées au post
            foreach ($post->getPostImages() as $image) {
                // Supprimer le fichier de l'image
                if (!empty($image->getFilename())) {
                    $imagePath = $this->getParameter('uploads_directory') . '/' . $image->getFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }

                // Supprimer l'image de la base de données
                $manager->remove($image);
            }

            $manager->remove($post);
            $manager->flush();

            $this->addFlash(
                'success',
                "<strong>".$post->getTitle()."</strong> deleted successfully !"
                );
    
            return $this->redirectToRoute('posts_index');
    }

    #[Route("/posts/{slug}/pictures", name: "post_pictures")]
    #[IsGranted('ROLE_USER')]
    public function displayPictures(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post): Response
    {
        $images = $post->getPostImages();

        return $this->render("posts/display_pictures.html.twig", [
            'post' => $post,
            'images' => $images
        ]);
    }

    #[Route("/posts/{slug}/add-image", name: "post_add_image")]
    #[IsGranted('ROLE_USER')]
    public function addImage(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        if (count($post->getPostImages()) >= 5) {
            $this->addFlash('danger', 'Limit of 5 pictures reached. Please delete one before adding a new image.');
            return $this->redirectToRoute('post_pictures', ['slug' => $post->getSlug()]);
        }

        $postImage = new PostImage();
        $form = $this->createForm(PostImageType::class, $postImage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $postImage->getFile();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    return $e->getMessage();
                }

                $postImage->setFilename($newFilename);
                $postImage->setPost($post);
                $manager->persist($postImage);
                $manager->flush();

                $this->addFlash('success', 'New image added successfully!');
                return $this->redirectToRoute('post_pictures', ['slug' => $post->getSlug()]);
            }
        }

        return $this->render('posts/add_image.html.twig', [
            'post' => $post,
            'myForm' => $form->createView(),
        ]);
    }

    /**
     * Delete picture
     *
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route("picture-delete/{id}", name: "post_picture_delete")]
    #[IsGranted('ROLE_USER')]
    public function deletePicture(#[MapEntity(mapping: ['id' => 'id'])] PostImage $postImage, EntityManagerInterface $manager): Response
    {
        // Get the associated post before deleting the image
        $post = $postImage->getPost();

        if (count($post->getPostImages()) <= 1) {
            $this->addFlash('danger', 'A post must have at least one image. Add another image first before deleting this one.');
            return $this->redirectToRoute('post_pictures', [
                'slug' => $post->getSlug(),
            ]);
        }
        
        // Remove the image file if it exists
        if (!empty($postImage->getFilename())) {
            unlink($this->getParameter('uploads_directory') . '/' . $postImage->getFilename());
            $manager->remove($postImage);
        }
        
        $manager->flush();
        
        $this->addFlash('success', 'Picture deleted!');
        
        return $this->redirectToRoute('post_pictures', [
            'slug' => $post->getSlug(),
        ]);
    }
}
