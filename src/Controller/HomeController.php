<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\TeamRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(ReviewRepository $reviewRepo, TeamRepository $teamRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $reviews = $reviewRepo->findTopRatedReviews();
        $teams = $teamRepo->findAll();
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        //handle form
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($contact);
            $manager->flush();

            $form = $this->createForm(ContactType::class, new Contact());//clear form with a new one

            $this->addFlash(
                'success',
                'Thank you for your request ! We will get back to you as soon as possible !'    
            );
            return new RedirectResponse($this->generateUrl('homepage').'#slide-contact');
            $this->redirect($this->generateUrl('homepage') . '#slide-contact');
        }


        return $this->render('home.html.twig', [
            'myForm' => $form->createView(),
            'reviews' => $reviews,
            'teams' => $teams
        ]);
    }

    #[Route('/private-policy', name: 'private_policy')]
    public function policy()
    {
        return $this->render('policy.html.twig', [

        ]);
    }
}
