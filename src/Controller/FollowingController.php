<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Following;
use App\Service\NotificationService;
use App\Repository\FollowingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    public function toggleFollow(#[MapEntity(mapping: ['slug' => 'slug'])] User $userToToggle, EntityManagerInterface $manager, FollowingRepository $followRepo): RedirectResponse {
        $user = $this->getUser();

        if ($user && $user !== $userToToggle) {
            $existingFollowing = $followRepo->findOneBy(['followerUser' => $user, 'followedUser' => $userToToggle]);

            if ($existingFollowing) {
                // If the user is already following, then unfollow
                $manager->remove($existingFollowing);
                $manager->flush();
                                
                $this->addFlash(
                    'success',
                    "You have unfollowed ".$userToToggle->getPseudo()."."
                );

                return $this->redirectToRoute('posts_index');
            } else {
                // If the user is not following, then follow
                $following = new Following();
                $following->setFollowerUser($user)
                          ->setFollowedUser($userToToggle);
                $manager->persist($following);
                $manager->flush();

                $this->notificationService->addNotification('follow', $user, null, $userToToggle);

                $this->addFlash(
                    'success',
                    "You are now following ".$userToToggle->getPseudo()."!"
                );

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
