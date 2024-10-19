<?php

namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use Symfony\Component\Mime\Email;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use App\Repository\OrderRepository;
use App\Repository\ReportRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReportController extends AbstractController
{
    /**
     * Generate new report
     *
     * @param UserRepository $userRepo
     * @param PostRepository $postRepo
     * @param CommentRepository $commentRepo
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param string $type
     * @param integer $id
     * @return Response
     */
    #[Route('/report/{type}/{id}', name: 'report_item')]
    #[IsGranted('ROLE_USER')]
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

    /**
     * Display all reports
     *
     * @param integer $page
     * @param ReportRepository $reportRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/moderation/reports/{page<\d+>?1}', name: 'reports_index')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function index(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy([], ['timestamp' => 'DESC']);

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

    /**
     * Display reports - type post
     *
     * @param integer $page
     * @param ReportRepository $reportRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/moderation/reports/posts/{page<\d+>?1}', name: 'reports_posts')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function reportsPosts(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'post'], ['timestamp' => 'DESC']);

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

    /**
     * Display reports - type comment
     *
     * @param integer $page
     * @param ReportRepository $reportRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/moderation/reports/comments/{page<\d+>?1}', name: 'reports_comments')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function reportsComments(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'comment'], ['timestamp' => 'DESC']);

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

    /**
     * Display reports - type user
     *
     * @param integer $page
     * @param ReportRepository $reportRepository
     * @param PaginationService $paginationService
     * @return Response
     */
    #[Route('/moderation/reports/users/{page<\d+>?1}', name: 'reports_users')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function reportsUsers(int $page, ReportRepository $reportRepository, PaginationService $paginationService): Response
    {
        $reports = $reportRepository->findBy(['type' => 'user'], ['timestamp' => 'DESC']);

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

    /**
     * mark report as reported
     */
    #[Route('/moderation/reports/{id}/validate', name: 'report_validate')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function validate(
        #[MapEntity(mapping: ['id' => 'id'])] Report $report,
        UserRepository $userRepo,
        ConversationRepository $convRepo,
        ReportRepository $reportRepo,
        CommentRepository $commentRepo,
        OrderRepository $orderRepo,
        EntityManagerInterface $manager,
        MailerInterface $mailer
    ): RedirectResponse
    {
        if ($report->getReportedPost()) {
            // handle reported post
            foreach ($report->getReportedPost()->getPostImages() as $image) {
                if (!empty($image->getFilename())) {
                    $imagePath = $this->getParameter('uploads_directory') . '/' . $image->getFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                $manager->remove($image);
            }
            $email = (new Email())
                ->from('info@cutestorm.kimberley-quenon.be')
                ->to($report->getReportedPost()->getAuthor()->getEmail())
                ->replyTo($report->getReportedPost()->getAuthor()->getEmail())
                ->subject("Reported Post")
                ->html($this->renderView('mail/reportedPost.html.twig', [
                    'user' => $report->getReportedPost()->getAuthor(),
                    'report' => $report
            ]));
            $mailer->send($email);

            $manager->remove($report->getReportedPost());
            $manager->remove($report);
            $manager->flush();
    
        } elseif ($report->getReportedComment()) {
            // handle reported comment
            $anon = $userRepo->findOneBy(['email'=>'anon@noreply.com']);
            $comment = $report->getReportedComment();
        
            $email = (new Email())
                ->from('info@cutestorm.kimberley-quenon.be')
                ->to($comment->getAuthor()->getEmail())
                ->replyTo($comment->getAuthor()->getEmail())
                ->subject("Reported Comment")
                ->html($this->renderView('mail/reportedComment.html.twig', [
                    'user' => $comment->getAuthor(),
                    'report' => $report
            ]));
            $mailer->send($email);

            $comment->setContent('This comment has been reported.')
            ->setAuthor($anon);

            $manager->remove($report);
            
            $manager->persist($comment);
            $manager->flush();
            
    
        } elseif ($report->getReportedUser()) {
            // handle reported user
            $user = $report->getReportedUser();
            $reportCount = $user->getReportCount();
    
            //if user had less than 3 reports: just a mail => otherwise delete profile
            if ($reportCount < 3) {
                // add a report to the count
                $user->incrementReportCount();
                $manager->persist($user);

                $email = (new Email())
                    ->from('info@cutestorm.kimberley-quenon.be')
                    ->to($user->getEmail())
                    ->replyTo($user->getEmail())
                    ->subject("Reported Account")
                    ->html($this->renderView('mail/reportedAccount.html.twig', [
                        'user' => $user,
                        'report' => $report
                ]));
                $mailer->send($email);

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

                    $email = (new Email())
                        ->from('info@cutestorm.kimberley-quenon.be')
                        ->to($user->getEmail())
                        ->replyTo($user->getEmail())
                        ->subject("Banned account")
                        ->html($this->renderView('mail/bannedAccount.html.twig', [
                            'user' => $user,
                    ]));
        
                    $mailer->send($email);
    
                    $manager->remove($user);
                    $manager->remove($report);
                    $manager->flush();
    
                    $this->addFlash('danger', 'This user is banned for good now...');
                }
            }
        }
    
        return $this->redirectToRoute('reports_index');
    }
    

    /**
     * keep the reported object
     */
    #[Route('/moderation/reports/{id}/keep', name: 'report_reject')]
    #[IsGranted(
        attribute: new Expression('(is_granted("ROLE_MODERATOR")) or is_granted("ROLE_ADMIN")'),
        message: "You are not allowed to see this"
    )]
    public function keep(#[MapEntity(mapping: ['id' => 'id'])] Report $report, ReportRepository $reportRepo, EntityManagerInterface $manager): RedirectResponse
    {
        // type of entity reported
        $reportedPost = $report->getReportedPost();
        $reportedComment = $report->getReportedComment();
        $reportedUser = $report->getReportedUser();
    
        // find all reports about this object
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
    
        // delete them all
        foreach ($relatedReports as $relatedReport) {
            $manager->remove($relatedReport);
        }
    
        //and this report too
        $manager->remove($report);
        $manager->flush();
    
        $this->addFlash('success', 'All reports about this object has been deleted.');
    
        return $this->redirectToRoute('reports_index');
    }
    
}
