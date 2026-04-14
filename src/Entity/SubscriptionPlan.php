<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Repository\SubscriptionPlanRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SubscriptionPlan
 *
 * Represents a subscription plan in the system.
 *
 * Note: ORM mapping is defined in XML (SubscriptionPlan.orm.xml)
 */
class SubscriptionPlan
{
    /**
     * The unique identifier of the subscription plan.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * The name of the subscription plan.
     *
     * @var string|null
     */
    private ?string $name = null;

    /**
     * The URL-friendly unique slug of the plan.
     *
     * @var string|null
     */
    private ?string $slug = null;

    /**
     * A description of the plan.
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * Base price of the plan.
     *
     * @var float|null
     */
    private ?float $price = null;

    /**
     * Optional discount percentage (0-100%).
     *
     * @var float|null
     */
    private ?float $discountPercent = null;

    /**
     * Currency code (e.g., EUR, USD).
     *
     * @var string|null
     */
    private ?string $currency = 'USD';

    /**
     * Billing interval for the plan (month or year).
     *
     * @var string|null
     */
    private ?string $billingInterval = null;

    /**
     * List of features included in the plan (stored as JSON).
     *
     * @var array
     */
    private array $features = [];

    /**
     * Whether the plan is active.
     *
     * @var bool
     */
    private bool $isActive = true;

    /**
     * Optional trial period in days.
     *
     * @var int|null
     */
    private ?int $trialDays = null;

    /**
     * Optional flag to mark featured plans.
     *
     * @var bool|null
     */
    private ?bool $isFeatured = false;

    /**
     * Optional sort order for displaying plans.
     *
     * @var int|null
     */
    private ?int $sortOrder = null;

    /**
     * Timestamp of creation.
     *
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $createdAt;

    /**
     * Timestamp of last update.
     *
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $updatedAt;

    /**
     * Collection of subscriptions associated with this plan.
     *
     * @var Collection<int, Subscription>
     */
    private Collection $subscriptions;

    /**
     * Constructor.
     *
     * Initializes default values for timestamps.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
    }

    /**
     * Get the unique identifier of the plan.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the name of the plan.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the name of the plan.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the slug of the plan.
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Set the slug of the plan.
     *
     * @param string $slug
     * @return static
     */
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the description of the plan.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of the plan.
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the base price of the plan.
     *
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * Set the base price of the plan.
     *
     * @param float $price
     * @return static
     */
    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get the discount percentage of the plan.
     *
     * @return float|null
     */
    public function getDiscountPercent(): ?float
    {
        return $this->discountPercent;
    }

    /**
     * Set the discount percentage of the plan.
     *
     * @param float|null $discountPercent
     * @return static
     */
    public function setDiscountPercent(?float $discountPercent): static
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    /**
     * Get the currency of the plan.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Set the currency of the plan.
     *
     * @param string|null $currency
     * @return static
     */
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the billing interval of the plan.
     *
     * @return string|null
     */
    public function getBillingInterval(): ?string
    {
        return $this->billingInterval;
    }

    /**
     * Set the billing interval of the plan.
     *
     * @param string $billingInterval
     * @return static
     */
    public function setBillingInterval(string $billingInterval): static
    {
        $this->billingInterval = $billingInterval;

        return $this;
    }

    /**
     * Get the features included in the plan.
     *
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Set the features included in the plan.
     *
     * @param array $features
     * @return static
     */
    public function setFeatures(array $features): static
    {
        $this->features = $features;

        return $this;
    }

    /**
     * Check if the plan is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set whether the plan is active.
     *
     * @param bool $isActive
     * @return static
     */
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get the trial period in days.
     *
     * @return int|null
     */
    public function getTrialDays(): ?int
    {
        return $this->trialDays;
    }

    /**
     * Set the trial period in days.
     *
     * @param int|null $trialDays
     * @return static
     */
    public function setTrialDays(?int $trialDays): static
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Check if the plan is featured.
     *
     * @return bool|null
     */
    public function getIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    /**
     * Set whether the plan is featured.
     *
     * @param bool|null $isFeatured
     * @return static
     */
    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    /**
     * Get the sort order of the plan.
     *
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * Set the sort order of the plan.
     *
     * @param int|null $sortOrder
     * @return static
     */
    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get the creation timestamp.
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
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
     * Get the last updated timestamp.
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the last updated timestamp.
     *
     * @param DateTimeImmutable $updatedAt
     * @return static
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get all subscriptions associated with this plan.
     *
     * @return Collection<int, Subscription> Returns a Doctrine Collection of Subscription entities
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * Add a subscription to this plan.
     *
     * Ensures that the owning side of the relation is updated as well.
     *
     * @param Subscription $subscription The subscription to add
     * @return static Returns the current SubscriptionPlan instance (for method chaining)
     */
    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setSubscriptionPlan($this);
        }

        return $this;
    }

    /**
     * Remove a subscription from this plan.
     *
     * Also sets the owning side (Subscription.subscriptionPlan) to null if it was pointing to this plan.
     *
     * @param Subscription $subscription The subscription to remove
     * @return static Returns the current SubscriptionPlan instance (for method chaining)
     */
    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getSubscriptionPlan() === $this) {
                $subscription->setSubscriptionPlan(null);
            }
        }

        return $this;
    }

    /**
     * Returns a human-readable label for the billing interval.
     *
     * Maps the internal `billingInterval` value to a friendly string suitable
     * for display in the UI.
     *
     * - 'month' => 'Billed monthly'
     * - 'year'  => 'Billed yearly'
     * - any other value => 'Custom billing'
     *
     * @return string The label describing the billing frequency
     */
    public function getBillingLabel(): string
    {
        return match ($this->billingInterval) {
            'month' => 'Billed monthly',
            'year' => 'Billed yearly',
            default => 'Custom billing'
        };
    }

}