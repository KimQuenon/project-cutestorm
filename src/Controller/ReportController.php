<?php

namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
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

    #[Route('/moderation/reports', name: 'reports_index')]
    #[IsGranted('ROLE_MODERATOR')]
    public function index(ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findAll();

        return $this->render('reports/index.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/moderation/reports/posts', name: 'reports_posts')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsPosts(ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findBy(['type' => 'post']);

        return $this->render('reports/posts.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/moderation/reports/comments', name: 'reports_comments')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsComments(ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findBy(['type' => 'comment']);

        return $this->render('reports/comments.html.twig', [
            'reports' => $reports,
        ]);
    }

    #[Route('/moderation/reports/users', name: 'reports_users')]
    #[IsGranted('ROLE_MODERATOR')]
    public function reportsUsers(ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findBy(['type' => 'user']);

        return $this->render('reports/users.html.twig', [
            'reports' => $reports,
        ]);
    }
    
    #[Route('/moderation/reports/{id}', name: 'report_show')]
    #[IsGranted('ROLE_MODERATOR')]
    public function show(#[MapEntity(mapping: ['id' => 'id'])] Report $report, ReportRepository $reportRepository): Response
    {

        return $this->render('reports/show.html.twig', [
            'report' => $report,
        ]);
    }


    #[Route('/moderation/reports/{id}/validate', name: 'report_validate')]
    #[IsGranted('ROLE_MODERATOR')]
    public function validate(#[MapEntity(mapping: ['id' => 'id'])] Report $report, UserRepository $userRepo, ConversationRepository $convRepo, ReportRepository $reportRepo, CommentRepository $commentRepo, EntityManagerInterface $manager): RedirectResponse
    {
        if ($report->getReportedPost()) {

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
            $anon = $userRepo->findOneBy(['email'=>'anon@noreply.com']);
            $comment = $report->getReportedComment();
    
            $comment->setContent('This comment has been reported.')
                    ->setAuthor($anon);
    
            $manager->persist($comment);
            $manager->flush();

        } elseif ($report->getReportedUser()) {

            $user = $report->getReportedUser();
            $user->incrementReportCount();
            
            if ($user->getReportCount() >= 3) {
                $user = $report->getReportedUser();
                $anon = $userRepo->findOneBy(['email'=>'anon@noreply.com']);
                $convRepo->replaceUserInConversations($user, $anon);
                $commentRepo->replaceAuthorInComments($user, $anon);

                foreach ($user->getLikeComments() as $likeComment) {
                    $manager->remove($likeComment);
                }

                $reportsByUser = $reportRepo->findBy(['reportedBy' => $user]);
                foreach ($reportsByUser as $userReport) {
                    $manager->remove($userReport);
                }

                // Notify user before deleting
                // $email = (new Email())
                //     ->from('no-reply@yourdomain.com')
                //     ->to($user->getEmail())
                //     ->subject('Account Banned')
                //     ->text('Your account has been banned due to multiple reports and will be deleted shortly.');
        
                // $mailer->send($email);

                $manager->remove($user);

                $this->addFlash('danger', 'L\'utilisateur a été banni après plusieurs signalements.');
            }
        }

        // Marquer le signalement comme traité
        $manager->remove($report);
        $manager->flush();

        $this->addFlash('success', 'Le signalement a été traité.');

        return $this->redirectToRoute('reports_index');
    }

    #[Route('/moderation/reports/{id}/keep', name: 'report_reject')]
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
