<?php

namespace App\Controller;

use App\Entity\Following;
use App\Entity\FollowRequest;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FollowRequestRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FollowRequestController extends AbstractController
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    #[Route('/requests', name: 'requests_index')]
    #[IsGranted('ROLE_USER')]
    public function index(FollowRequestRepository $requestRepo): Response
    {
        $user = $this->getUser();

        $requests = $requestRepo->findBy(['sentTo' => $user]);

        return $this->render('follow_request/index.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/requests/{id}/accept', name: 'request_accept')]
    #[IsGranted('ROLE_USER')]
    public function acceptRequest(#[MapEntity(mapping: ['id' => 'id'])] FollowRequest $request, FollowRequestRepository $requestRepo, EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if ($request && $request->getSentTo() === $user) {
            // Create following relationship
            $following = new Following();
            $following->setFollowerUser($request->getSentBy())
                      ->setFollowedUser($user);

            $manager->persist($following);

            $this->notificationService->addNotification('request', $user, $request->getSentBy(), null, null);

            // Remove the follow request
            $manager->remove($request);

            $manager->flush();

            $this->addFlash(
                'success',
                "You have accepted the follow request from " . $request->getSentBy()->getPseudo() . "."
            );
        } else {
            $this->addFlash(
                'error',
                "Invalid follow request."
            );
        }

        return $this->redirectToRoute('requests_index');
    }

    #[Route('/requests/{id}/reject', name: 'request_reject')]
    #[IsGranted('ROLE_USER')]
    public function rejectRequest(#[MapEntity(mapping: ['id' => 'id'])] FollowRequest $request, FollowRequestRepository $requestRepo, EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if ($request && $request->getSentTo() === $user) {
            // Remove the follow request
            $manager->remove($request);
            $manager->flush();

            $this->addFlash(
                'success',
                "You have rejected the follow request from " . $request->getSentBy()->getPseudo() . "."
            );
        } else {
            $this->addFlash(
                'error',
                "Invalid follow request."
            );
        }

        return $this->redirectToRoute('requests_index');
    }
}
