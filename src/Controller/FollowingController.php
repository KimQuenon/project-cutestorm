<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Following;
use App\Entity\FollowRequest;
use App\Service\NotificationService;
use App\Repository\FollowingRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use App\Repository\FollowRequestRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FollowingController extends AbstractController
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    #[Route('/toggle-follow/{slug}', name: 'toggle_follow')]
    #[IsGranted('ROLE_USER')]
    public function toggleFollow(#[MapEntity(mapping: ['slug' => 'slug'])] User $userToToggle, EntityManagerInterface $manager, FollowingRepository $followRepo, FollowRequestRepository $followRequestRepo, NotificationRepository $notificationRepo): RedirectResponse {
        $user = $this->getUser();

        if ($user && $user !== $userToToggle) {
            $existingFollowing = $followRepo->findOneBy(['followerUser' => $user, 'followedUser' => $userToToggle]);

            if ($existingFollowing) {
                // If the user is already following, then unfollow
                $manager->remove($existingFollowing);

                $notification = $notificationRepo->findOneBy([
                    'type' => 'follow',
                    'relatedUser' => $userToToggle,
                    'user' => $user
                ]);


                if($notification){
                    $manager->remove($notification);
                }
                
                $manager->flush();
                
                $this->addFlash(
                    'success',
                    "You have unfollowed " . $userToToggle->getPseudo() . "."
                );

                return $this->redirectToRoute('posts_index');
            } else {
                // If the user is not following, then check privacy setting
                if ($userToToggle->isPrivate()) {
                    // Create a follow request
                    $followRequest = new FollowRequest();
                    $followRequest->setSentBy($user)
                                  ->setSentTo($userToToggle)
                                  ->setStatus(false); // Request is pending
                    $manager->persist($followRequest);
                    $manager->flush();
                    
                    $this->addFlash(
                        'success',
                        "Follow request sent to " . $userToToggle->getPseudo() . "."
                    );

                    return $this->redirectToRoute('posts_index');
                } else {
                    // Proceed with following directly
                    $following = new Following();
                    $following->setFollowerUser($user)
                              ->setFollowedUser($userToToggle);
                    $manager->persist($following);
                    $manager->flush();

                    $this->notificationService->addNotification('follow', $user, $userToToggle, null, null);

                    $this->addFlash(
                        'success',
                        "You are now following " . $userToToggle->getPseudo() . "!"
                    );
                }

                return $this->redirectToRoute('profile_show', ['slug' => $userToToggle->getSlug()]);
            }
        } else {
            $this->addFlash(
                'error',
                "You cannot follow or unfollow this user."
            );
        }
    }
}
