<?php

namespace App\Entity;

use App\Repository\ComplaintRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComplaintRepository::class)]
#[ORM\Table(name: 'complaint')]
// Existing indexes
#[ORM\Index(columns: ['status'],     name: 'idx_complaint_status')]
#[ORM\Index(columns: ['priority'],   name: 'idx_complaint_priority')]
#[ORM\Index(columns: ['created_at'], name: 'idx_complaint_created_at')]
// NEW: index on FK used in joins/filters
#[ORM\Index(columns: ['assigned_to_id'], name: 'idx_complaint_assigned_to')]
// NEW: composite index for the admin list query (ORDER BY priority DESC, created_at DESC)
#[ORM\Index(columns: ['status', 'priority', 'created_at'], name: 'idx_complaint_list_sort')]
// NEW: index for AVG(resolved_at) query
#[ORM\Index(columns: ['resolved_at'], name: 'idx_complaint_resolved_at')]
#[ORM\HasLifecycleCallbacks]
class Complaint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Subject is required')]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: 'Subject must be at least {{ limit }} characters long',
        maxMessage: 'Subject cannot be longer than {{ limit }} characters'
    )]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Description must be at least {{ limit }} characters long'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50, enumType: ComplaintCategory::class)]
    #[Assert\NotNull(message: 'Category is required')]
    private ?ComplaintCategory $category = null;

    #[ORM\Column(length: 20, enumType: ComplaintStatus::class)]
    private ComplaintStatus $status = ComplaintStatus::PENDING;

    #[ORM\Column(length: 20, enumType: ComplaintPriority::class)]
    private ComplaintPriority $priority = ComplaintPriority::MEDIUM;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $submittedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminResponse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resolutionNotes = null;

    // Store only the filename — never a full path with user-controlled segments.
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[\w\-]+\.[a-zA-Z0-9]{1,10}$/',
        message: 'Invalid attachment filename'
    )]
    private ?string $attachmentPath = null;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status    = ComplaintStatus::PENDING;
        $this->priority  = ComplaintPriority::MEDIUM;
    }

    // -------------------------------------------------------------------------
    // Lifecycle callbacks
    // -------------------------------------------------------------------------

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Getters / setters
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
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

    public function getCategory(): ?ComplaintCategory
    {
        return $this->category;
    }

    public function setCategory(ComplaintCategory $category): static
    {
        $this->category = $category;

        // Auto-suggest priority based on category only when still at default.
        if ($this->priority === ComplaintPriority::MEDIUM) {
            $this->priority = $category->getDefaultPriority();
        }

        return $this;
    }

    public function getStatus(): ComplaintStatus
    {
        return $this->status;
    }

    /**
     * @throws \LogicException when the transition is not allowed.
     */
    public function setStatus(ComplaintStatus $status): static
    {
        if ($this->status !== $status) {
            $allowed = $this->status->allowedTransitions();

            if (!in_array($status, $allowed, true)) {
                throw new \LogicException(sprintf(
                    'Cannot transition complaint from "%s" to "%s".',
                    $this->status->value,
                    $status->value
                ));
            }

            $this->status    = $status;
            $this->updatedAt = new \DateTimeImmutable();

            if ($status->isFinal() && $this->resolvedAt === null) {
                $this->resolvedAt = new \DateTimeImmutable();
            }
        }

        return $this;
    }

    /**
     * Force-set status without transition checks (admin override / data migrations).
     */
    public function forceStatus(ComplaintStatus $status): static
    {
        $this->status    = $status;
        $this->updatedAt = new \DateTimeImmutable();

        if ($status->isFinal() && $this->resolvedAt === null) {
            $this->resolvedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPriority(): ComplaintPriority
    {
        return $this->priority;
    }

    public function setPriority(ComplaintPriority $priority): static
    {
        $this->priority  = $priority;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $submittedBy): static
    {
        $this->submittedBy = $submittedBy;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        $this->updatedAt  = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getAdminResponse(): ?string
    {
        return $this->adminResponse;
    }

    public function setAdminResponse(?string $adminResponse): static
    {
        $this->adminResponse = $adminResponse;
        $this->updatedAt     = new \DateTimeImmutable();
        return $this;
    }

    public function getResolutionNotes(): ?string
    {
        return $this->resolutionNotes;
    }

    public function setResolutionNotes(?string $resolutionNotes): static
    {
        $this->resolutionNotes = $resolutionNotes;
        return $this;
    }

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function setAttachmentPath(?string $attachmentPath): static
    {
        $this->attachmentPath = $attachmentPath;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Convenience status helpers
    // -------------------------------------------------------------------------

    public function isResolved(): bool
    {
        return $this->status->isFinal();
    }

    public function isPending(): bool
    {
        return $this->status === ComplaintStatus::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === ComplaintStatus::IN_PROGRESS;
    }

    public function isUnassigned(): bool
    {
        return $this->assignedTo === null;
    }
}