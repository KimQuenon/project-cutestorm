<?php

namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\ReportRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
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

        $reportedEntity = null;
        switch ($type) {
            case 'user':
                $reportedEntity = $userRepo->find($id);
                break;
            case 'post':
                $reportedEntity = $postRepo->find($id);
                break;
            case 'comment':
                $reportedEntity = $commentRepo->find($id);
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

            return $this->redirectToRoute('posts_index');
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
    public function validate(#[MapEntity(mapping: ['id' => 'id'])] Report $report, UserRepository $userRepo, EntityManagerInterface $manager): RedirectResponse
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
            
            if ($user->getSignalementCount() >= 3) {
                $user->setBanned(true);
                $this->addFlash('danger', 'L\'utilisateur a été banni après plusieurs signalements.');
            }
        }

        // Marquer le signalement comme traité
        $manager->remove($report);
        $manager->flush();

        $this->addFlash('success', 'Le signalement a été traité.');

        return $this->redirectToRoute('reports_index');
    }
}
