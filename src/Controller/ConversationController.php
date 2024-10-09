<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Message;
use App\Form\MessageType;
use App\Entity\Conversation;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConversationController extends AbstractController
{
    /**
     * display all conversations
     *
     * @param ConversationRepository $convRepo
     * @param MessageRepository $messageRepo
     * @return Response
     */
    #[Route('/profile/conversations', name: 'conversations_index')]
    #[IsGranted('ROLE_USER')]
    public function index(ConversationRepository $convRepo, MessageRepository $messageRepo): Response
    {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);
     
        $unreadCounts = [];
        $totalUnread = 0;
    
        foreach ($conversations as $conversation) {
            $unreadCount = $messageRepo->countUnreadMessages($conversation, $user);
            $unreadCounts[$conversation->getId()] = $unreadCount;
            $totalUnread += $unreadCount;
        }
    
        return $this->render('profile/conversations/index.html.twig', [
            'conversations' => $conversations,
            'unreadCounts' => $unreadCounts,
            'totalUnread' => $totalUnread,
            'pendingRequests' => null,
        ]);
    }
    
    /**
     * display all conversations requests
     *
     * @param ConversationRepository $convRepo
     * @return Response
     */
    #[Route('/profile/conversations/requests', name: 'conversation_requests')]
    #[IsGranted('ROLE_USER')]
    public function requests(ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $pendingRequests = $convRepo->findPendingRequests($user);

        return $this->render('profile/conversations/requests.html.twig', [
            'pendingRequests' => $pendingRequests,
            'totalUnread' => null
        ]);
    }

    /**
     * display single conversation
     */
    #[Route('/profile/conversations/{slug}', name: 'conversation_show')]
    #[IsGranted('ROLE_USER')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $otherUser,
        Request $request,
        EntityManagerInterface $manager,
        ConversationRepository $convRepo,
        MessageRepository $messageRepo,
    ): Response {
        $user = $this->getUser();
        $conversations = $convRepo->findByUser($user);
        $pendingRequests = $convRepo->findPendingRequests($user);
        $conversation = $convRepo->findConversationByUsers($user, $otherUser);
    
        if (!$conversation) {
            throw $this->createNotFoundException("Conversation not found.");
        }
    
        $messages = $conversation->getMessagesSorted();

        foreach ($messages as $message) {
            if ($message->getSender() !== $user && !$message->isRead()) {
                $message->setRead(true);
                $manager->persist($message);
            }
        }
        $manager->flush(); 

        $unreadCounts = [];
        $totalUnread = 0;
    
        foreach ($conversations as $convo) {
            $unreadCount = $messageRepo->countUnreadMessages($convo, $user);
            $unreadCounts[$convo->getId()] = $unreadCount;
            $totalUnread += $unreadCount;
        }
    
        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);
    
        $form->handleRequest($request);
    
        if($form->isSubmitted() && $form->isValid())
        {
            $newMessage->setConversation($conversation)
                        ->setSender($user)
                        ->setRead(false);
    
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
    
        if ($conversation->isAccepted()) {
            return $this->render('profile/conversations/show.html.twig', [
                'myForm' => $form->createView(),
                'conversation' => $conversation,
                'conversations' => $conversations,
                'messages' => $messages,
                'otherUser' => $otherUser,
                'unreadCounts' => $unreadCounts,
                'totalUnread' => $totalUnread,
                'pendingRequests' => null,
            ]);
        } else {
            return $this->render('profile/conversations/show_request.html.twig', [
                'conversation' => $conversation,
                'messages' => $messages,
                'otherUser' => $otherUser,
                'conversations' => $conversations,
                'pendingRequests'=> $pendingRequests,
                'totalUnread' => null
            ]);
        }
    }
    

    /**
     * create conversation
     */
    #[Route('/conversations/create/{slug}', name: 'conversation_new')]
    #[IsGranted('ROLE_USER')]
    public function create(
        #[MapEntity(mapping: ['slug' => 'slug'])] User $otherUser, Request $request, EntityManagerInterface $entityManager,
        UserRepository $userRepo,
        ConversationRepository $convRepo,
        MessageRepository $messageRepo
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
        $conversation->setSender($user)
                    ->setRecipient($otherUser)
                    ->setAccepted(false);
    
        //init first message
        $initialMessage = new Message();
        $initialMessage->setRead(false);

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

        $unreadCounts = [];
        $totalUnread = 0;
    
        foreach ($conversations as $conversation) {
            $unreadCount = $messageRepo->countUnreadMessages($conversation, $user);
            $unreadCounts[$conversation->getId()] = $unreadCount;
            $totalUnread += $unreadCount;
        }
    
        return $this->render('profile/conversations/create.html.twig', [
            'form' => $form->createView(),
            'otherUser'=> $otherUser,
            'conversations'=> $conversations,
            'totalUnread' => $totalUnread
        ]);
    }
    
    /**
     * accept request
     */
    #[Route('/conversations/accept/{slug}', name: 'conversation_accept')]
    #[IsGranted('ROLE_USER')]
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
    

    /**
     * reject request
     */
    #[Route('/conversations/reject/{slug}', name: 'conversation_reject')]
    #[IsGranted('ROLE_USER')]
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
