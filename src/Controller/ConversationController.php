<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConversationController extends AbstractController
{
    #[Route('/profile/conversations', name: 'conversations_index')]
    public function index(ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $conversations = $convRepo->sortConvByRecentMsg($user);

        return $this->render('profile/conversations/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/profile/conversations/{id}', name: 'conversation_show')]
    public function show(#[MapEntity(mapping: ['id' => 'id'])] Conversation $conversation, ConversationRepository $convRepo): Response
    {
        $user = $this->getUser();
        $conversations = $convRepo->sortConvByRecentMsg($user);
        $conversation = $convRepo->findOneById($conversation);
        $messages = $conversation->getMessagesSorted();


        return $this->render('profile/conversations/show.html.twig', [
            'conversations' => $conversations,
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }
}
