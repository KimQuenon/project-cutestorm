<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Component\Mime\Email;
use App\Repository\TeamRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * homepage + display employees + contact
     *
     * @param ReviewRepository $reviewRepo
     * @param TeamRepository $teamRepo
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/', name: 'homepage')]
    public function index(ReviewRepository $reviewRepo, TeamRepository $teamRepo, Request $request, EntityManagerInterface $manager, MailerInterface $mailer): Response
    {
        $reviews = $reviewRepo->findTopRatedReviews();
        $teams = $teamRepo->findAll();

        $team1 = $teamRepo->findBy(['level' => 1]);
        $teams2 = $teamRepo->findBy(['level' => 2]);
        $teams3 = $teamRepo->findBy(['level' => 3]);

        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        //handle form
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($contact);
            $manager->flush();
            $email = (new Email())
                ->from('info@cutestorm.kimberley-quenon.be')
                ->to('kimberley.quenon.02@gmail.com')
                ->replyTo($contact->getEmail())
                ->subject("New message from contact form")
                ->html($this->renderView('mail/contact.html.twig', [
                    'contact' => $contact,
                ]));

            $mailer->send($email);

            $form = $this->createForm(ContactType::class, new Contact());//clear form with a new one

            $this->addFlash(
                'success',
                'Thank you for your request ! We will get back to you as soon as possible !'    
            );
            return new RedirectResponse($this->generateUrl('homepage').'#contact-slide');
        }


        return $this->render('home.html.twig', [
            'myForm' => $form->createView(),
            'reviews' => $reviews,
            'team1' => $team1,
            'teams2' => $teams2,
            'teams3' => $teams3,
        ]);
    }

    /**
     * legals mentions
     *
     * @return void
     */
    #[Route('/private-policy', name: 'private_policy')]
    public function policy()
    {
        return $this->render('privacy.html.twig', [

        ]);
    }
}
