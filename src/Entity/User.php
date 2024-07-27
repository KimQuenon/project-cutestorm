<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: "An account is already associated with this email address, please modify it.")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['pseudo'], message: "This pseudo is already taken, please choose another one.")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email(message: "Invalid email address")]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\Length(min: 8, max: 255, minMessage: "Your password must be at least 8 characters.", maxMessage: "Your password should not be longer than 255 characters.")]
    private ?string $password = null;

    #[Assert\EqualTo(propertyPath: "password", message: "Unconfirmed password")]
    public ?string $passwordConfirm = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 5, max: 50, minMessage: "This field must be at least 5 characters long.", maxMessage: "This field can't be longer than 50 characters.")]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 2, max: 50, minMessage: "This field must be at least 2 characters long.", maxMessage: "This field can't be longer than 50 characters.")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 2, max: 50, minMessage: "This field must be at least 2 characters long.", maxMessage: "This field can't be longer than 50 characters.")]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 10, max: 100, minMessage: "This field must be at least 10 characters long.", maxMessage: "This field can't be longer than 100 characters.")]
    private ?string $address = null;

    #[ORM\Column]
    #[Assert\Range(min: 1000, max: 999999, notInRangeMessage: "Postal code must be between 1000 and 999999.")]
    private ?int $postalcode = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 2, max: 100, minMessage: "This field must be at least 2 characters long.", maxMessage: "This field can't be longer than 100 characters.")]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Country is required.")]
    private ?string $country = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(min: 5, minMessage: "The description must be at least 5 characters long.")]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Image(mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'], mimeTypesMessage: "Upload a jpg, jpeg, png or gif file")]
    #[Assert\File(maxSize: "1024k", maxSizeMessage: "This file is too big to be uploaded.")]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Image(mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'], mimeTypesMessage: "Upload a jpg, jpeg, png or gif file")]
    #[Assert\File(maxSize: "1024k", maxSizeMessage: "This file is too big to be uploaded.")]
    private ?string $banner = null;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $likes;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'relatedUser', orphanRemoval: true)]
    private Collection $relatedNotifications;

    #[ORM\OneToMany(mappedBy: 'followerUser', targetEntity: Following::class)]
    private Collection $followings;

    #[ORM\OneToMany(mappedBy: 'followedUser', targetEntity: Following::class)]
    private Collection $followedByUsers;

    #[ORM\Column]
    private ?bool $isPrivate = null;

    /**
     * @var Collection<int, FollowRequest>
     */
    #[ORM\OneToMany(targetEntity: FollowRequest::class, mappedBy: 'sentBy', orphanRemoval: true)]
    private Collection $sentRequests;

    /**
     * @var Collection<int, FollowRequest>
     */
    #[ORM\OneToMany(targetEntity: FollowRequest::class, mappedBy: 'sentTo', orphanRemoval: true)]
    private Collection $receivedRequests;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, LikeComment>
     */
    #[ORM\OneToMany(targetEntity: LikeComment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $likeComments;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->relatedNotifications = new ArrayCollection();
        $this->followings = new ArrayCollection();
        $this->followedByUsers = new ArrayCollection();
        $this->sentRequests = new ArrayCollection();
        $this->receivedRequests = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->likeComments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug(): void
    {
        if (empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->pseudo);
        }
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (empty($this->timestamp)) {
            $this->timestamp = new \DateTime();
        }
    }

    public function getFullName(): string
    {
        return $this->firstname . " " . $this->lastname;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalcode(): ?int
    {
        return $this->postalcode;
    }

    public function setPostalcode(int $postalcode): static
    {
        $this->postalcode = $postalcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getRelatedNotifications(): Collection
    {
        return $this->relatedNotifications;
    }

    public function addRelatedNotification(Notification $relatedNotification): static
    {
        if (!$this->relatedNotifications->contains($relatedNotification)) {
            $this->relatedNotifications->add($relatedNotification);
            $relatedNotification->setRelatedUser($this);
        }

        return $this;
    }

    public function removeRelatedNotification(Notification $relatedNotification): static
    {
        if ($this->relatedNotifications->removeElement($relatedNotification)) {
            if ($relatedNotification->getRelatedUser() === $this) {
                $relatedNotification->setRelatedUser(null);
            }
        }

        return $this;
    }

    public function getFollowings(): Collection
    {
        return $this->followings;
    }

    public function addFollowing(Following $following): static
    {
        if (!$this->followings->contains($following)) {
            $this->followings->add($following);
            $following->setFollower($this);
        }

        return $this;
    }

    public function removeFollowing(Following $following): static
    {
        if ($this->followings->removeElement($following)) {
            if ($following->getFollower() === $this) {
                $following->setFollower(null);
            }
        }

        return $this;
    }

    public function getFolloweds(): Collection
    {
        return $this->followeds;
    }

    public function addFollowed(Following $followed): static
    {
        if (!$this->followeds->contains($followed)) {
            $this->followeds->add($followed);
            $followed->setFollowed($this);
        }

        return $this;
    }

    public function removeFollowed(Following $followed): static
    {
        if ($this->followeds->removeElement($followed)) {
            if ($followed->getFollowed() === $this) {
                $followed->setFollowed(null);
            }
        }

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * @return Collection<int, FollowRequest>
     */
    public function getSentRequests(): Collection
    {
        return $this->sentRequests;
    }

    public function addSentRequest(FollowRequest $sentRequest): static
    {
        if (!$this->sentRequests->contains($sentRequest)) {
            $this->sentRequests->add($sentRequest);
            $sentRequest->setSentBy($this);
        }

        return $this;
    }

    public function removeSentRequest(FollowRequest $sentRequest): static
    {
        if ($this->sentRequests->removeElement($sentRequest)) {
            // set the owning side to null (unless already changed)
            if ($sentRequest->getSentBy() === $this) {
                $sentRequest->setSentBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FollowRequest>
     */
    public function getReceivedRequests(): Collection
    {
        return $this->receivedRequests;
    }

    public function addReceivedRequest(FollowRequest $receivedRequest): static
    {
        if (!$this->receivedRequests->contains($receivedRequest)) {
            $this->receivedRequests->add($receivedRequest);
            $receivedRequest->setSentTo($this);
        }

        return $this;
    }

    public function removeReceivedRequest(FollowRequest $receivedRequest): static
    {
        if ($this->receivedRequests->removeElement($receivedRequest)) {
            // set the owning side to null (unless already changed)
            if ($receivedRequest->getSentTo() === $this) {
                $receivedRequest->setSentTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LikeComment>
     */
    public function getLikeComments(): Collection
    {
        return $this->likeComments;
    }

    public function addLikeComment(LikeComment $likeComment): static
    {
        if (!$this->likeComments->contains($likeComment)) {
            $this->likeComments->add($likeComment);
            $likeComment->setUser($this);
        }

        return $this;
    }

    public function removeLikeComment(LikeComment $likeComment): static
    {
        if ($this->likeComments->removeElement($likeComment)) {
            // set the owning side to null (unless already changed)
            if ($likeComment->getUser() === $this) {
                $likeComment->setUser(null);
            }
        }

        return $this;
    }
}

