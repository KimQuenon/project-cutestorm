<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
        $posts = $postRepo->findBy([], ['timestamp' => 'DESC']);

        return $this->render('posts/index.html.twig', [
            "posts"=> $posts
        ]);
    }

    #[Route("/posts/new", name:"post_create")]
    public function create(Request $request, EntityManagerInterface $manager): Response
    {
        $post = new Post();
        $form = $this->createform(PostType::class, $post);

        $form->handleRequest($request);

        //form complet et valid -> envoi bdd + message et redirection
        if($form->isSubmitted() && $form->IsValid())
        {
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
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post): Response
    {
        return $this->render("posts/show.html.twig", [
            'post' => $post,
        ]);
    }

    #[Route("/posts/{slug}/edit", name: "post_edit")]
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

    #[Route("/posts/{slug}/pictures", name: "post_pictures")]
    public function displayPictures(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post): Response
    {
        $images = $post->getPostImages();

        return $this->render("posts/display_pictures.html.twig", [
            'post' => $post,
            'images' => $images
        ]);
    }
    
}
