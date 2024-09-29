<?php

namespace App\Entity;

use App\Entity\User;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PostRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 5, max:100, minMessage:"The title must be at least 5 characters long.", maxMessage: "The title can't be longer than 100 characters.")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(min: 2, minMessage:"The description must be at least 2 characters long.")]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    /**
     * @var Collection<int, PostImage>
     */
    #[ORM\OneToMany(targetEntity: PostImage::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $postImages;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $likes;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $notifications;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'reportedPost', cascade: ['remove'], orphanRemoval: true)]
    private Collection $reports;

    #[ORM\Column]
    private ?bool $commentDisabled = false;

    public function __construct()
    {
        $this->postImages = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * init slug
     *
     * @return void
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug(): void
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->title.' '.uniqid());
    }

    /**
     * init datetime
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


    public function isLikedByUser(?User $user): bool
{
    foreach ($this->likes as $like) {
        if ($like->getUser() === $user) {
            return true;
        }
    }
    return false;
}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, PostImage>
     */
    public function getPostImages(): Collection
    {
        return $this->postImages;
    }

    public function addPostImage(PostImage $postImage): static
    {
        if (!$this->postImages->contains($postImage)) {
            $this->postImages->add($postImage);
            $postImage->setPost($this);
        }

        return $this;
    }

    public function removePostImage(PostImage $postImage): static
    {
        if ($this->postImages->removeElement($postImage)) {
            // set the owning side to null (unless already changed)
            if ($postImage->getPost() === $this) {
                $postImage->setPost(null);
            }
        }

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, Like>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setPost($this);
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getPost() === $this) {
                $like->setPost(null);
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
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }

    public function isCommentDisabled(): ?bool
    {
        return $this->commentDisabled;
    }

    public function setCommentDisabled(bool $commentDisabled): static
    {
        $this->commentDisabled = $commentDisabled;

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setReportedPost($this);
        }

        return $this;
    }

    public function removeReport(Report $report): static
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getReportedPost() === $this) {
                $report->setReportedPost(null);
            }
        }

        return $this;
    }
}
