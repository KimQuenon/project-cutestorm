<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    #[Route('/feed', name: 'profile_feed')]
    #[IsGranted('ROLE_USER')]
    public function feed(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/feed.html.twig', [
            'user'=>$user,
        ]);
    }

    #[Route('/profile/{slug}', name: 'profile_show')]
    public function viewProfile(#[MapEntity(mapping: ['slug' => 'slug'])] User $user): Response
    {
        return $this->render('profile/show.html.twig', [
            'user'=>$user,
        ]);
    }
}
