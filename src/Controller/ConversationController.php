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

    #[Route('/profile/conversations/{slug}', name: 'conversation_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $otherUser,
        Request $request,
        EntityManagerInterface $manager,
        ConversationRepository $convRepo
    ): Response {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);
        $pendingRequests = $convRepo->findPendingRequests($user);
        
        // Rechercher la conversation en utilisant l'utilisateur et le slug
        $conversation = $convRepo->findConversationByUsers($user, $otherUser);
    
        if (!$conversation) {
            throw $this->createNotFoundException("Conversation not found.");
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
                'slug' => $otherUser->getSlug()
            ]);
        }
    
        // Rediriger vers le template approprié en fonction de l'acceptation de la conversation
        if ($conversation->isAccepted()) {
            return $this->render('profile/conversations/show.html.twig', [
                'myForm' => $form->createView(),
                'conversation' => $conversation,
                'conversations' => $conversations,
                'messages' => $messages,
                'otherUser' => $otherUser,
            ]);
        } else {
            return $this->render('profile/conversations/show_request.html.twig', [
                'conversation' => $conversation,
                'messages' => $messages,
                'otherUser' => $otherUser,
                'conversations' => $conversations,
                'pendingRequests'=> $pendingRequests
            ]);
        }
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
            return $this->redirectToRoute('conversation_show', ['slug' => $otherUser->getSlug()]);
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
            return $this->redirectToRoute('conversation_show', ['slug' => $otherUser->getSlug()]);
        }
    
        return $this->render('profile/conversations/create.html.twig', [
            'form' => $form->createView(),
            'otherUser'=> $otherUser,
            'conversations'=> $conversations
        ]);
    }
    

    #[Route('/conversations/accept/{slug}', name: 'conversation_accept')]
    public function accept(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $sender,
        EntityManagerInterface $manager,
        ConversationRepository $convRepo
    ): RedirectResponse {
        $recipient = $this->getUser();
        
        // Rechercher la conversation par sender et recipient
        $conversation = $convRepo->findConversationByUsers($recipient, $sender);
    
        if (!$conversation || $conversation->getRecipient() !== $recipient) {
            throw $this->createAccessDeniedException("You are not authorized to accept this request.");
        }
    
        $conversation->setAccepted(true);
        $manager->persist($conversation);
        $manager->flush();
    
        $this->addFlash('success', 'Conversation accepted.');
    
        return $this->redirectToRoute('conversation_show', [
            'slug' => $sender->getSlug()
        ]);
    }
    


    #[Route('/conversations/reject/{slug}', name: 'conversation_reject')]
    public function reject(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $sender,
        EntityManagerInterface $manager,
        ConversationRepository $convRepo
    ): RedirectResponse {
        $recipient = $this->getUser();
        
        // Rechercher la conversation par sender et recipient
        $conversation = $convRepo->findConversationByUsers($recipient, $sender);
    
        if (!$conversation || $conversation->getRecipient() !== $recipient) {
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
