<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Service\PaginationService;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminContactController extends AbstractController
{
    #[Route('/admin/contacts/{page<\d+>?1}', name: 'contact_index')]
    /**
     * Admin - Display all messages sent from the contact form
     *
     * @param integer $page
     * @param ContactRepository $contactRepo
     * @param PaginationService $paginationService
     * @return Response
     */
    public function contact(int $page, ContactRepository $contactRepo, PaginationService $paginationService): Response
    {
        $contacts = $contactRepo->findBy([], ['id' => 'DESC']);

        $currentPage = $page;
        $itemsPerPage = 15;
    
        $pagination = $paginationService->paginate($contacts, $currentPage, $itemsPerPage);
        $contactsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/contact/index.html.twig', [
            'contacts' => $contactsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * Mark as read
     *
     * @param ContactRepository $contactRepo
     * @return Response
     */
    #[Route('/admin/contact/mark-read', name: 'mark_contact_read')]
    public function markRead(ContactRepository $contactRepo): Response
    {
        $contactRepo->markAllAsRead();

        return $this->redirectToRoute('contact_index');
    }

    #[Route("/admin/contact/show/{id}", name: "contact_show")]
    public function show(#[MapEntity(mapping: ['id' => 'id'])] Contact $contact, EntityManagerInterface $manager): Response
    {
        if($contact->setRead(false)){
            $contact->setRead(true);
            $manager->flush();
        }

        return $this->render("admin/contact/show.html.twig", [
            'contact' => $contact,
        ]);
    }

    /**
     * Delete message
     */
    #[Route("admin/contact/{id}/delete", name:"contact_delete")]
    public function deleteContact(#[MapEntity(mapping: ['id' => 'id'])] Contact $contact, EntityManagerInterface $manager): Response
    {
            $manager->remove($contact);
            $manager->flush();

            $this->addFlash(
                'success',
                "Le message de <strong>".$contact->getName()." ".$contact->getLastName()."</strong> a bien été supprimé!"
            );

        return $this->redirectToRoute('admin_contact_index');
    }
}
