<?php

namespace App\Entity;

use App\Entity\Payment;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use App\Repository\SubscriptionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represents a subscription of a user to a subscription plan.
 *
 * Captures price snapshot, status, billing information, trial, pause, cancellation,
 * and keeps track of the user and the subscription plan.
 */
class Subscription
{
    /**
     * The unique identifier of the subscription.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Snapshot of the plan price at the moment of subscription.
     *
     * @var float|null
     */
    private ?float $priceSnapshot = null;

    /**
     * Snapshot of the currency code (e.g., USD, EUR) at the moment of subscription.
     *
     * @var string|null
     */
    private ?string $currencySnapshot = null;

    /**
     * Snapshot of the billing interval (month or year) at the moment of subscription.
     *
     * @var string|null
     */
    private ?string $billingIntervalSnapshot = null;

    /**
     * Snapshot of the discount percentage applied to the subscription.
     *
     * @var float|null
     */
    private ?float $discountPercentSnapshot = null;

    /**
     * Current status of the subscription.
     *
     * @var string|null Values defined in SubscriptionStatus enum
     */
    private ?string $status = null;

    /**
     * Timestamp when the subscription started.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $startedAt = null;

    /**
     * Timestamp when the subscription ends (if cancelled or expired).
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $endsAt = null;

    /**
     * Timestamp of the next billing cycle.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $nextBillingAt = null;

    /**
     * Timestamp when the subscription was cancelled.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $cancelledAt = null;

    /**
     * Timestamp when the subscription was paused.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $pausedAt = null;

    /**
     * Timestamp when the trial period ends.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $trialEndsAt = null;

    /**
     * Timestamp of creation.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Timestamp of last update.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * The user who owns this subscription.
     *
     * @var User|null
     */
    private ?User $user = null;

    /**
     * The subscription plan associated with this subscription.
     *
     * @var SubscriptionPlan|null
     */

    private ?SubscriptionPlan $subscriptionPlan = null;

    /**
     * @var Collection<int, Payment>
     */
    private Collection $payments;

    /**
     * Constructor.
     *
     * Initializes timestamps and default status.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->status = SubscriptionStatus::ACTIVE->value;
        $this->payments = new ArrayCollection();
    }

    /**
     * Get subscription ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get price snapshot.
     *
     * @return float|null
     */
    public function getPriceSnapshot(): ?float
    {
        return $this->priceSnapshot;
    }

    /**
     * Set price snapshot.
     *
     * @param float $priceSnapshot
     * @return static
     */
    public function setPriceSnapshot(float $priceSnapshot): static
    {
        $this->priceSnapshot = $priceSnapshot;
        return $this;
    }

    /**
     * Get currency snapshot.
     *
     * @return string|null
     */
    public function getCurrencySnapshot(): ?string
    {
        return $this->currencySnapshot;
    }

    /**
     * Set currency snapshot.
     *
     * @param string|null $currencySnapshot
     * @return static
     */
    public function setCurrencySnapshot(?string $currencySnapshot): static
    {
        $this->currencySnapshot = $currencySnapshot;
        return $this;
    }

    /**
     * Get billing interval snapshot.
     *
     * @return string|null
     */
    public function getBillingIntervalSnapshot(): ?string
    {
        return $this->billingIntervalSnapshot;
    }

    /**
     * Set billing interval snapshot.
     *
     * @param string $billingIntervalSnapshot
     * @return static
     */
    public function setBillingIntervalSnapshot(string $billingIntervalSnapshot): static
    {
        $this->billingIntervalSnapshot = $billingIntervalSnapshot;
        return $this;
    }

    /**
     * Get discount percentage snapshot.
     *
     * @return float|null
     */
    public function getDiscountPercentSnapshot(): ?float
    {
        return $this->discountPercentSnapshot;
    }

    /**
     * Set discount percentage snapshot.
     *
     * @param float|null $discountPercentSnapshot
     * @return static
     */
    public function setDiscountPercentSnapshot(?float $discountPercentSnapshot): static
    {
        $this->discountPercentSnapshot = $discountPercentSnapshot;
        return $this;
    }

    /**
     * Get current status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set subscription status.
     *
     * @param string $status Must be a valid SubscriptionStatus value
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get subscription start timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Set subscription start timestamp.
     *
     * @param DateTimeImmutable $startedAt
     * @return static
     */
    public function setStartedAt(DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * Get subscription end timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    /**
     * Set subscription end timestamp.
     *
     * @param DateTimeImmutable|null $endsAt
     * @return static
     */
    public function setEndsAt(?DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;
        return $this;
    }

    /**
     * Get next billing timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getNextBillingAt(): ?DateTimeImmutable
    {
        return $this->nextBillingAt;
    }

    /**
     * Set next billing timestamp.
     *
     * @param DateTimeImmutable|null $nextBillingAt
     * @return static
     */
    public function setNextBillingAt(?DateTimeImmutable $nextBillingAt): static
    {
        $this->nextBillingAt = $nextBillingAt;
        return $this;
    }

    /**
     * Get cancelled timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getCancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /**
     * Set cancelled timestamp.
     *
     * @param DateTimeImmutable|null $cancelledAt
     * @return static
     */
    public function setCancelledAt(?DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    /**
     * Get paused timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getPausedAt(): ?DateTimeImmutable
    {
        return $this->pausedAt;
    }

    /**
     * Set paused timestamp.
     *
     * @param DateTimeImmutable|null $pausedAt
     * @return static
     */
    public function setPausedAt(?DateTimeImmutable $pausedAt): static
    {
        $this->pausedAt = $pausedAt;
        return $this;
    }

    /**
     * Get trial end timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getTrialEndsAt(): ?DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    /**
     * Set trial end timestamp.
     *
     * @param DateTimeImmutable|null $trialEndsAt
     * @return static
     */
    public function setTrialEndsAt(?DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    /**
     * Get creation timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set creation timestamp.
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
     * Get last update timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set last update timestamp.
     *
     * @param DateTimeImmutable|null $updatedAt
     * @return static
     */
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Check if subscription is currently active.
     *
     * @return bool True if status is ACTIVE
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE->value;
    }

    /**
     * Check if subscription is still in trial period.
     *
     * @return bool True if trialEndsAt is set and in the future
     */
    public function isInTrial(): bool
    {
        return $this->trialEndsAt !== null && $this->trialEndsAt > new DateTimeImmutable();
    }

    /**
     * Get current price considering discount snapshot.
     *
     * @return float
     */
    public function getCurrentPrice(): float
    {
        if ($this->discountPercentSnapshot) {
            return $this->priceSnapshot * (1 - $this->discountPercentSnapshot / 100);
        }

        return $this->priceSnapshot;
    }

    /**
     * Cancel subscription.
     *
     * Sets status to CANCELLED, records cancelledAt, and sets endsAt to next billing date.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->status = SubscriptionStatus::CANCELLED->value;
        $this->cancelledAt = new DateTimeImmutable();
        $this->endsAt = $this->nextBillingAt;
    }

    /**
     * Pause subscription.
     *
     * Sets status to PAUSED and records pausedAt timestamp.
     *
     * @return void
     */
    public function pause(): void
    {
        $this->status = SubscriptionStatus::PAUSED->value;
        $this->pausedAt = new DateTimeImmutable();
    }

    /**
     * Resume a paused subscription.
     *
     * Adjusts nextBillingAt to account for paused duration and sets status to ACTIVE.
     *
     * @return void
     */
    public function resume(): void
    {
        if (!$this->pausedAt) {
            return;
        }

        $now = new DateTimeImmutable();
        $pausedDuration = $this->pausedAt->diff($now);

        if ($this->nextBillingAt) {
            $this->nextBillingAt = $this->nextBillingAt->add($pausedDuration);
        }

        $this->pausedAt = null;
        $this->status = SubscriptionStatus::ACTIVE->value;
    }

    /**
     * Renew subscription to the next billing period.
     *
     * @return void
     */
    public function renew(): void
    {
        if (!$this->nextBillingAt) return;

        $interval = match ($this->billingIntervalSnapshot) {
            'month' => '+1 month',
            'year' => '+1 year',
            default => '+1 month'
        };

        $this->nextBillingAt = $this->nextBillingAt->modify($interval);
    }

    /**
     * Get associated user.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set associated user.
     *
     * @param User|null $user
     * @return static
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get associated subscription plan.
     *
     * @return SubscriptionPlan|null
     */
    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    /**
     * Set associated subscription plan.
     *
     * @param SubscriptionPlan|null $subscriptionPlan
     * @return static
     */
    public function setSubscriptionPlan(?SubscriptionPlan $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;
        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setSubscription($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getSubscription() === $this) {
                $payment->setSubscription(null);
            }
        }

        return $this;
    }

}