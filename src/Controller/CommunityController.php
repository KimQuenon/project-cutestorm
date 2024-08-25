<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommunityController extends AbstractController
{
    #[Route('/community', name: 'community')]
    public function index(): Response
    {
        return $this->render('community.html.twig', [
            'controller_name' => 'CommunityController',
        ]);
    }
}
