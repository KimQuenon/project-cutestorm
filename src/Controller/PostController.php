<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Entity\Comment;
use App\Form\ReplyType;
use App\Entity\PostImage;
use App\Form\CommentType;
use App\Form\PostImageType;
use App\Service\SearchService;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Service\PaginationService;
use App\Repository\ReportRepository;
use App\Service\NotificationService;
use App\Repository\FollowingRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LikeCommentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    private $searchService;
    public function __construct(NotificationService $notificationService, EntityManagerInterface $entityManager, SearchService $searchService)
    {
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
        $this->searchService = $searchService;
    }
    
    /**
     * Display all posts
     *
     * @param PostRepository $postRepo
     * @return Response
     */
    #[Route('/posts/{page<\d+>?1}', name: 'posts_index')]
    public function index(int $page, PostRepository $postRepo, LikeRepository $likeRepo, FollowingRepository $followingRepo, ReportRepository $reportRepo, PaginationService $paginationService): Response
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

        $currentPage = $page;
        $itemsPerPage = 10;

        // Use the pagination service to get paginated results
        $pagination = $paginationService->paginate($visiblePosts, $currentPage, $itemsPerPage);
        $paginatedPosts = $pagination['items'];
        $totalPages = $pagination['totalPages'];
    
        // Get the posts liked by the current user
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
    
        $reportedPostIds = [];
        if ($user) {
            foreach ($visiblePosts as $post) {
                if ($reportRepo->hasUserReportedPost($user, $post)) {
                    $reportedPostIds[] = $post->getId();
                }
            }
        }

        return $this->render('posts/index.html.twig', [
            'posts' => $paginatedPosts,
            'likedPostSlugs' => $likedPostSlugs,
            'reportedPostIds' => $reportedPostIds,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }
    
    #[Route('/posts/search/ajax', name: 'posts_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        if (empty($query)) {
            return new JsonResponse([]);
        }

        $results = $this->searchService->search($query, 'post');

        return new JsonResponse($results);
    }
    
    // #[Route('/posts/search/ajax', name: 'posts_search_ajax', methods: ['GET'])]
    // public function searchAjax(Request $request, PostRepository $postRepo): JsonResponse
    // {
    //     $query = $request->query->get('query', '');

    //     if (empty($query)) {
    //         return new JsonResponse([]);
    //     }

    //     $results = $postRepo->findByTitleOrPseudoQuery($query)
    //         ->setMaxResults(10)
    //         ->getQuery()
    //         ->getResult();

    //     $jsonResults = array_map(function ($post) {
    //         return [
    //             'title' => $post->getTitle(),
    //             'author'=> $post->getAuthor()->getPseudo(),
    //             'slug' => $post->getSlug(),
    //         ];
    //     }, $results);

    //     return new JsonResponse($jsonResults);
    // }

    #[Route("/post/new", name:"post_create")]
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

    #[Route("/post/{slug}", name: "post_show")]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, LikeRepository $likeRepo, LikeCommentRepository $likeCommentRepo, FollowingRepository $followingRepo, ReportRepository $reportRepo, Request $request, EntityManagerInterface $manager): Response {
        $user = $this->getUser();
        $author = $post->getAuthor();
        $isPrivate = $author->isPrivate();
        $areCommentsDisabled = $post->isCommentDisabled();
        
        // Determine visibility based on author's privacy settings and user's subscription
        $canViewPost = !$isPrivate || $user === $author || $followingRepo->isFollowing($user, $author);
    
        if (!$canViewPost) {
            $this->addFlash('danger', 'This publication is private or you do not have permission to view it.');
            return $this->redirectToRoute('posts_index');
        }
        
        // Get liked posts for the current user
        $likedPosts = $likeRepo->findBy(['user' => $user]);
        $likedPostSlugs = array_map(fn($like) => $like->getPost()->getSlug(), $likedPosts);
    
        $likedComments = $likeCommentRepo->findBy(['user' => $user]);
        $likedCommentIds = array_map(fn($like) => $like->getComment()->getId(), $likedComments);
    
        // Fetch comments only if comments are not disabled
        $comments = $areCommentsDisabled ? [] : $post->getComments();
    
        $form = null; // if comments are disabled -> avoid rendering the form
    
        // Create a form for a new comment only if comments are enabled
        if (!$areCommentsDisabled) {
            $comment = new Comment();
            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                $comment->setPost($post)
                    ->setAuthor($user);
                $manager->persist($comment);
                $manager->flush();
    
                $this->notificationService->addNotification('comment', $user, $author, $post, $comment);
    
                $form = $this->createForm(CommentType::class);
    
                $this->addFlash('success', 'Comment posted');
            }
        }

        
        $replyForms = [];
        foreach ($comments as $comment) {
            if ($comment->getParent() === null) {
                $replyForm = $this->createForm(ReplyType::class, new Comment(), [
                    'parent' => $comment,
                ]);
                $replyForms[$comment->getId()] = $replyForm->createView();
            }
        }
    
        $reportedCommentIds = [];
        if($user){
            foreach ($comments as $comment) {
                if ($reportRepo->hasUserReportedComment($user, $comment)) {
                    $reportedCommentIds[] = $comment->getId();
                }
            }
        }

        $reportedPostIds = [];
        if ($user) {
            if ($reportRepo->hasUserReportedPost($user, $post)) {
                $reportedPostIds[] = $post->getId();
            }
        }

        $otherPosts = $author->getPosts()->toArray();

        usort($otherPosts, function ($a, $b) {
            return $b->getId() <=> $a->getId();
        });
        
        $otherPosts = array_filter($otherPosts, function ($otherPost) use ($post) {
            return $otherPost !== $post;
        });
        
        $latestOtherPosts = array_slice($otherPosts, 0, 3);

        return $this->render("posts/show.html.twig", [
            'post' => $post,
            'likedPostSlugs' => $likedPostSlugs,
            'likedCommentIds' => $likedCommentIds,
            'comments' => $comments,
            'myForm' => $form ? $form->createView() : null,
            'replyForms' => $replyForms,
            'areCommentsDisabled' => $areCommentsDisabled,
            'reportedCommentsIds' => $reportedCommentIds,
            'reportedPostIds' => $reportedPostIds,
            'latestOtherPosts' => $latestOtherPosts
        ]);
    }
    

    #[Route("/post/{slug}/edit", name: "post_edit")]
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

    #[Route("/post/{slug}/delete", name:"post_delete")]
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

    #[Route("/post/{slug}/add-image", name: "post_add_image")]
    #[IsGranted('ROLE_USER')]
    public function addImage(#[MapEntity(mapping: ['slug' => 'slug'])] Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        if (count($post->getPostImages()) >= 5) {
            $this->addFlash('danger', 'Limit of 5 pictures reached. Please delete one before adding a new image.');
            return $this->redirectToRoute('post_edit', ['slug' => $post->getSlug()]);
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
                return $this->redirectToRoute('post_edit', ['slug' => $post->getSlug()]);
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
    #[Route("post/picture-delete/{id}", name: "post_picture_delete")]
    #[IsGranted('ROLE_USER')]
    public function deletePicture(#[MapEntity(mapping: ['id' => 'id'])] PostImage $postImage, EntityManagerInterface $manager): Response
    {
        // Get the associated post before deleting the image
        $post = $postImage->getPost();

        if (count($post->getPostImages()) <= 1) {
            $this->addFlash('danger', 'A post must have at least one image. Add another image first before deleting this one.');
            return $this->redirectToRoute('post_add_image', [
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
        
        return $this->redirectToRoute('post_edit', [
            'slug' => $post->getSlug(),
        ]);
    }
}
