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

    #[Route('/moderation/reports', name: 'report_index')]
    #[IsGranted('ROLE_MODERATOR')]
    public function index(ReportRepository $reportRepository): Response
    {
        $reports = $reportRepository->findAll();

        return $this->render('reports/index.html.twig', [
            'reports' => $reports,
        ]);
    }
}
