<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\ActivityLogRepository;
use DateTimeImmutable;

/**
 * Represents an activity log entry for auditing or dashboard purposes.
 *
 * Each entry records an event, its type, description, the related entity (if any),
 * the user who triggered it, and the creation timestamp.
 *
 * This entity can be used to generate an admin activity feed or for audit trails.
 */
class ActivityLog
{
    /**
     * Unique identifier of the activity log entry.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Type of activity (e.g., subscription_created, payment_succeeded).
     *
     * @var string|null
     */
    private ?string $type = null;

    /**
     * Human-readable description of the activity.
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * ID of the related entity (e.g., subscription, payment).
     * Optional: may be null if the activity is general.
     *
     * @var int|null
     */
    private ?int $relatedId = null;

    /**
     * Type of the related entity (e.g., 'subscription', 'payment').
     * Optional: may be null if the activity is general.
     *
     * @var string|null
     */
    private ?string $relatedType = null;

    /**
     * Timestamp when the activity was created.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * User who triggered the activity.
     * Optional: may be null for system-generated events.
     *
     * @var User|null
     */
    private ?User $user = null;

    /**
     * Constructor.
     *
     * Initializes default values createdAt timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Get the ID of the activity log entry.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the activity type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the activity type.
     *
     * @param string $type
     * @return static
     */
    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the activity description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the activity description.
     *
     * @param string $description
     * @return static
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the ID of the related entity.
     *
     * @return int|null
     */
    public function getRelatedId(): ?int
    {
        return $this->relatedId;
    }

    /**
     * Set the ID of the related entity.
     *
     * @param int|null $relatedId
     * @return static
     */
    public function setRelatedId(?int $relatedId): static
    {
        $this->relatedId = $relatedId;
        return $this;
    }

    /**
     * Get the type of the related entity.
     *
     * @return string|null
     */
    public function getRelatedType(): ?string
    {
        return $this->relatedType;
    }

    /**
     * Set the type of the related entity.
     *
     * @param string|null $relatedType
     * @return static
     */
    public function setRelatedType(?string $relatedType): static
    {
        $this->relatedType = $relatedType;
        return $this;
    }

    /**
     * Get the creation timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set the creation timestamp.
     *
     * @param DateTimeImmutable $createdAt
     * @return static
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the user who triggered this activity.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user who triggered this activity.
     *
     * @param User|null $user
     * @return static
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

}