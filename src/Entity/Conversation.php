<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'conversations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', orphanRemoval: true)]
    private Collection $messages;

    #[ORM\Column]
    private ?bool $isAccepted = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getLastMessage(): ?Message
    {
        // Tri par timestamp décroissant
        $messages = $this->getMessages()->toArray();
        usort($messages, function($a, $b) {
            return $b->getTimestamp() <=> $a->getTimestamp();
        });

        // Dernier message
        return count($messages) > 0 ? $messages[0] : null;
    }

    public function getMessagesSorted(): array
    {
        $messages = $this->getMessages()->toArray();
        usort($messages, function($a, $b) {
            return $a->getTimestamp() <=> $b->getTimestamp();
        });
        return $messages;
    }

    public function countUnreadMessagesForUser(User $user): int
    {
        $unreadCount = 0;
        foreach ($this->getMessages() as $message) {
            if ($message->getSender() !== $user && !$message->isRead()) {
                $unreadCount++;
            }
        }
        return $unreadCount;

    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // Set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function isAccepted(): ?bool
    {
        return $this->isAccepted;
    }

    public function setAccepted(bool $isAccepted): static
    {
        $this->isAccepted = $isAccepted;

        return $this;
    }
}
