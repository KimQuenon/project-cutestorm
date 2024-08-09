<?php

namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use App\Repository\OrderRepository;
use App\Repository\ReportRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReportController extends AbstractController
{
    #[Route('/report/{type}/{id}', name: 'report_item')]
    public function report(UserRepository $userRepo, PostRepository $postRepo, CommentRepository $commentRepo, Request $request, EntityManagerInterface $manager, string $type, int $id): Response
    {
        if (!in_array($type, ['user', 'post', 'comment'])) {
            throw $this->createNotFoundException('Invalid report type.');
        }

        $user = $this->getUser();

        switch ($type) {
            case 'user':
                $reportedEntity = $userRepo->find($id);
                // Check if the reported user is the current user
                if ($reportedEntity === $user) {
                    $this->addFlash('danger', 'You cannot report yourself.');
                    return $this->redirectToRoute('posts_index');
                }
                break;
            case 'post':
                $reportedEntity = $postRepo->find($id);
                // Check if the current user is the author of the post
                if ($reportedEntity->getAuthor() === $user) {
                    $this->addFlash('danger', 'You cannot report your own post.');
                    return $this->redirectToRoute('posts_index');
                }
                break;
            case 'comment':
                $reportedEntity = $commentRepo->find($id);
                // Check if the current user is the author of the comment
                if ($reportedEntity->getAuthor() === $user) {
                    $this->addFlash('danger', 'You cannot report your own comment.');
                    return $this->redirectToRoute('posts_index');
                }
                break;
        }

        if (!$reportedEntity) {
            throw $this->createNotFoundException('The reported entity was not found.');
        }

        $report = new Report();
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setType($type);
            $report->setReportedBy($this->getUser());
            $report->setReportedAt(new \DateTime());

            // Set the appropriate reported entity
            if ($type === 'post') {
                $report->setReportedPost($reportedEntity);
            } elseif ($type === 'comment') {
                $report->setReportedComment($reportedEntity);
            } elseif ($type === 'user') {
                $report->setReportedUser($reportedEntity);
            }

            $manager->persist($report);
            $manager->flush();

            $this->addFlash('success', 'Your report has been submitted.');

            if ($type === 'comment') {
                return $this->redirectToRoute('post_show', ['slug' => $reportedEntity->getPost()->getSlug()]);
            } elseif ($type === 'post' || $type=== 'user') {
                return $this->redirectToRoute('posts_index');
            }
        }

        return $this->render('reports/new.html.twig', [
            'myForm' => $form->createView(),
            'reportedEntity' => $reportedEntity,
        ]);
    }

    #[Route('admin/moderation/reports/{page<\d+>?1}', name: 'reports_index')]
    #[IsGranted('ROLE_MODERATOR')]
    public function index(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findAll();

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($reports, $currentPage, $itemsPerPage);
        $reportsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('reports/index.html.twig', [
            'reports' => $reportsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('admin/moderation/reports/posts/{page<\d+>?1}', name: 'reports_posts')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsPosts(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'post']);

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($reports, $currentPage, $itemsPerPage);
        $reportsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('reports/posts.html.twig', [
            'reports' => $reportsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('admin/moderation/reports/comments/{page<\d+>?1}', name: 'reports_comments')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsComments(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'comment']);

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($reports, $currentPage, $itemsPerPage);
        $reportsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('reports/comments.html.twig', [
            'reports' => $reportsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('admin/moderation/reports/users/{page<\d+>?1}', name: 'reports_users')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsUsers(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'user']);

        $currentPage = $page;
        $itemsPerPage = 20;

        $pagination = $paginationService->paginate($reports, $currentPage, $itemsPerPage);
        $reportsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('reports/users.html.twig', [
            'reports' => $reportsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }
    
    #[Route('admin/moderation/reports/{id}', name: 'report_show')]
    #[IsGranted('ROLE_MODERATOR')]
    public function show(#[MapEntity(mapping: ['id' => 'id'])] Report $report, ReportRepository $reportRepository): Response
    {

        return $this->render('reports/show.html.twig', [
            'report' => $report,
        ]);
    }


    #[Route('admin/moderation/reports/{id}/validate', name: 'report_validate')]
    #[IsGranted('ROLE_MODERATOR')]
    public function validate(
        #[MapEntity(mapping: ['id' => 'id'])] Report $report,
        UserRepository $userRepo,
        ConversationRepository $convRepo,
        ReportRepository $reportRepo,
        CommentRepository $commentRepo,
        OrderRepository $orderRepo,
        EntityManagerInterface $manager
    ): RedirectResponse
    {
        if ($report->getReportedPost()) {
            // Traitement du post signalé
            foreach ($report->getReportedPost()->getPostImages() as $image) {
                if (!empty($image->getFilename())) {
                    $imagePath = $this->getParameter('uploads_directory') . '/' . $image->getFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                $manager->remove($image);
            }
            $manager->remove($report->getReportedPost());
    
        } elseif ($report->getReportedComment()) {
            // Traitement du commentaire signalé
            $anon = $userRepo->findOneBy(['email'=>'anon@noreply.com']);
            $comment = $report->getReportedComment();
        
            $comment->setContent('This comment has been reported.')
                    ->setAuthor($anon);
        
            $manager->persist($comment);
            $manager->flush();
    
        } elseif ($report->getReportedUser()) {
            // Traitement de l'utilisateur signalé
            $user = $report->getReportedUser();
            $reportCount = $user->getReportCount();
    
            if ($reportCount < 3) {
                // Incrémente le compteur de signalements
                $user->incrementReportCount();
                $manager->persist($user);
                $manager->remove($report);
                $manager->flush();
    
                $this->addFlash('success', 'Le signalement a été traité.');
            } else {
                $unpaidOrders = $orderRepo->findUnpaidOrders($user);
    
                if ($unpaidOrders) {
                    $this->addFlash('warning', 'The user cannot be deleted as they have unpaid invoices.');
                } else {
                    $del = $userRepo->findOneBy(['email'=>'deleted@noreply.com']);
                    $convRepo->replaceUserInConversations($user, $del);
                    $commentRepo->replaceAuthorInComments($user, $del);
                    $orderRepo->replaceUserInOrders($user, $del);
    
                    foreach ($user->getLikeComments() as $likeComment) {
                        $manager->remove($likeComment);
                    }
    
                    $reportsByUser = $reportRepo->findBy(['reportedBy' => $user]);
                    foreach ($reportsByUser as $userReport) {
                        $manager->remove($userReport);
                    }

                    if ($user->getAvatar() && $user->getAvatar() !== 'default-avatar.jpg') {
                        unlink($this->getParameter('uploads_directory').'/'.$user->getAvatar());
                    }

                    if ($user->getBanner() && !in_array($user->getBanner(), ['banner1.jpg', 'banner2.jpg', 'banner3.jpg'])) {
                        unlink($this->getParameter('uploads_directory').'/'.$user->getBanner());
                    }
    
                    // Notify user before deleting
                    // $email = (new Email())
                    //     ->from('no-reply@yourdomain.com')
                    //     ->to($user->getEmail())
                    //     ->subject('Account Banned')
                    //     ->text('Your account has been banned due to multiple reports and will be deleted shortly.');
            
                    // $mailer->send($email);
    
                    $manager->remove($user);
                    $manager->remove($report);
                    $manager->flush();
    
                    $this->addFlash('danger', 'L\'utilisateur a été banni après plusieurs signalements.');
                }
            }
        }
    
        return $this->redirectToRoute('reports_index');
    }
    

    #[Route('admin/moderation/reports/{id}/keep', name: 'report_reject')]
    #[IsGranted('ROLE_MODERATOR')]
    public function keep(#[MapEntity(mapping: ['id' => 'id'])] Report $report, ReportRepository $reportRepo, EntityManagerInterface $manager): RedirectResponse
    {
        // Déterminer l'objet signalé
        $reportedPost = $report->getReportedPost();
        $reportedComment = $report->getReportedComment();
        $reportedUser = $report->getReportedUser();
    
        // Trouver tous les signalements associés à cet objet
        if ($reportedPost) {
            $relatedReports = $reportRepo->findBy(['reportedPost' => $reportedPost]);
        } elseif ($reportedComment) {
            $relatedReports = $reportRepo->findBy(['reportedComment' => $reportedComment]);
        } elseif ($reportedUser) {
            $relatedReports = $reportRepo->findBy(['reportedUser' => $reportedUser]);
        } else {
            $this->addFlash('error', 'Le signalement est invalide.');
            return $this->redirectToRoute('reports_index');
        }
    
        // Supprimer tous les signalements associés
        foreach ($relatedReports as $relatedReport) {
            $manager->remove($relatedReport);
        }
    
        // Supprimer le signalement actuel
        $manager->remove($report);
        $manager->flush();
    
        $this->addFlash('success', 'Le signalement et tous les signalements associés ont été supprimés.');
    
        return $this->redirectToRoute('reports_index');
    }
    
}
