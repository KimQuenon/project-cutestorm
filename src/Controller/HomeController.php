<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, EntityManagerInterface $manager): Response
    {
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
            'myForm' => $form->createView()
        ]);
    }
}
