<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Message;
use App\Form\MessageType;
use App\Entity\Conversation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConversationController extends AbstractController
{
    #[Route('/profile/conversations', name: 'conversations_index')]
    public function index(ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);

        return $this->render('profile/conversations/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/profile/conversations/requests', name: 'conversation_requests')]
    public function requests(ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $pendingRequests = $convRepo->findPendingRequests($user);

        return $this->render('profile/conversations/requests.html.twig', [
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/profile/conversations/{id}', name: 'conversation_show')]
    public function show(#[MapEntity(mapping: ['id' => 'id'])] Conversation $conversation, Request $request, EntityManagerInterface $manager, ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);
        
        // Vérifiez si l'utilisateur fait partie de la conversation
        if ($conversation->getSender() !== $user && $conversation->getRecipient() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette conversation.");
        }

        $messages = $conversation->getMessagesSorted();

        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $newMessage->setConversation($conversation);
            $newMessage->setSender($user);

            $manager->persist($newMessage);    
            $manager->flush();

            $this->addFlash(
                'success',
                "Message envoyé !"
            );

            return $this->redirectToRoute('conversation_show', [
                'id' => $conversation->getId()
            ]);
        }

        return $this->render('profile/conversations/show.html.twig', [
            'myForm' => $form->createView(),
            'conversation' => $conversation,
            'conversations' => $conversations,
            'messages' => $messages
        ]);
    }

    #[Route('/conversations/create/{slug}', name: 'conversation_new')]
    public function create(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $otherUser, Request $request, EntityManagerInterface $entityManager,
        UserRepository $userRepo,
        ConversationRepository $convRepo
    ): Response {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);

        if (!$otherUser) {
            $this->addFlash('danger', "User not found.");
            return $this->redirectToRoute('conversations_index');
        }
    
        $existingConversation = $convRepo->findOneBy([
            'sender' => $user,
            'recipient' => $otherUser,
        ]);
    
        if ($existingConversation) {
            $this->addFlash('warning', 'You already have a conversation going on...');
            return $this->redirectToRoute('conversation_show', ['id' => $existingConversation->getId()]);
        }
    
        $conversation = new Conversation();
        $conversation->setSender($user);
        $conversation->setRecipient($otherUser);
        $conversation->setAccepted(false);
    
        //init first message
        $initialMessage = new Message();
        $form = $this->createForm(MessageType::class, $initialMessage);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $initialMessage->setSender($user);
            $initialMessage->setConversation($conversation);
    
            $entityManager->persist($conversation);
            $entityManager->persist($initialMessage);
            $entityManager->flush();
    
            $this->addFlash('success', 'Awaiting response from ' . $otherUser->getPseudo() . '.');
    
            return $this->redirectToRoute('conversations_index');
        }
    
        return $this->render('profile/conversations/create.html.twig', [
            'form' => $form->createView(),
            'otherUser'=> $otherUser,
            'conversations'=> $conversations
        ]);
    }
    

    #[Route('/conversations/accept/{id}', name: 'conversation_accept')]
    public function accept(Conversation $conversation, EntityManagerInterface $manager): RedirectResponse
    {
        if ($conversation->getRecipient() !== $this->getUser()) {
            throw $this->createAccessDeniedException("You are not authorized to accept this request.");
        }

        $conversation->setAccepted(true);
        $manager->persist($conversation);
        $manager->flush();

        $this->addFlash('success', 'Conversation accepted.');
        return $this->redirectToRoute('conversation_show', [
            'id' => $conversation->getId()
        ]);
    }


    #[Route('/conversations/reject/{id}', name: 'conversation_reject')]
    public function reject(Conversation $conversation, EntityManagerInterface $manager): RedirectResponse
    {
        if ($conversation->getRecipient() !== $this->getUser()) {
            throw $this->createAccessDeniedException("You are not authorized to reject this request.");
        }

        // Supprimer la conversation et les messages associés
        foreach ($conversation->getMessages() as $message) {
            $manager->remove($message);
        }
        $manager->remove($conversation);
        $manager->flush();

        $this->addFlash('success', 'Conversation rejected and deleted.');
        return $this->redirectToRoute('conversation_requests');
    }
}
