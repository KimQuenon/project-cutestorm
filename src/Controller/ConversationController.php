<?php

namespace App\Controller;

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
        
        // Récupérer les conversations où l'utilisateur est impliqué
        $conversations = $convRepo->findByUser($user);

        return $this->render('profile/conversations/index.html.twig', [
            'conversations' => $conversations,
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

    #[Route('/conversations/create/{userPseudo}', name: 'conversation_new')]
    public function create(string $userPseudo, EntityManagerInterface $entityManager, UserRepository $userRepo, ConversationRepository $convRepo): RedirectResponse
    {
        $user = $this->getUser();

        // find the other user
        $otherUser = $userRepo->findOneBy(['pseudo' => $userPseudo]);

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

        // Créer une nouvelle conversation
        $conversation = new Conversation();
        $conversation->setSender($user);
        $conversation->setRecipient($otherUser);

        $entityManager->persist($conversation);
        $entityManager->flush();

        $this->addFlash('success', 'New conversation created, say hi !.');

        return $this->redirectToRoute('conversation_show', ['id' => $conversation->getId()]);
    }
}
