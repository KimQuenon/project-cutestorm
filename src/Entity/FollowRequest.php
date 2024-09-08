<?php

namespace App\Entity;

use App\Repository\FollowRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FollowRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sentRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sentBy = null;

    #[ORM\ManyToOne(inversedBy: 'receivedRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sentTo = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    /**
     * set datetime to current datetime
     *
     * @return void
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if(empty($this->timestamp))
        {
            $this->timestamp = new \DateTime();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): static
    {
        $this->sentBy = $sentBy;

        return $this;
    }

    public function getSentTo(): ?User
    {
        return $this->sentTo;
    }

    public function setSentTo(?User $sentTo): static
    {
        $this->sentTo = $sentTo;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
