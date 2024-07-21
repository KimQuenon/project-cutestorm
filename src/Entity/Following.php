<?php

namespace App\Entity;

use App\Repository\FollowingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowingRepository::class)]
class Following
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // L'utilisateur qui suit un autre utilisateur
    #[ORM\ManyToOne(inversedBy: 'followings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $followerUser = null;

    // L'utilisateur qui est suivi par un autre utilisateur
    #[ORM\ManyToOne(inversedBy: 'followedByUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $followedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollowerUser(): ?User
    {
        return $this->followerUser;
    }

    public function setFollowerUser(?User $followerUser): static
    {
        $this->followerUser = $followerUser;

        return $this;
    }

    public function getFollowedUser(): ?User
    {
        return $this->followedUser;
    }

    public function setFollowedUser(?User $followedUser): static
    {
        $this->followedUser = $followedUser;

        return $this;
    }
}
