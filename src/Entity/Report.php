<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reportedBy = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\ManyToOne]
    private ?Post $reportedPost = null;

    #[ORM\ManyToOne]
    private ?User $reportedUser = null;

    #[ORM\ManyToOne]
    private ?Comment $reportedComment = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): static
    {
        $this->reportedBy = $reportedBy;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getReportedPost(): ?Post
    {
        return $this->reportedPost;
    }

    public function setReportedPost(?Post $reportedPost): static
    {
        $this->reportedPost = $reportedPost;

        return $this;
    }

    public function getReportedUser(): ?User
    {
        return $this->reportedUser;
    }

    public function setReportedUser(?User $reportedUser): static
    {
        $this->reportedUser = $reportedUser;

        return $this;
    }

    public function getReportedComment(): ?Comment
    {
        return $this->reportedComment;
    }

    public function setReportedComment(?Comment $reportedComment): static
    {
        $this->reportedComment = $reportedComment;

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
