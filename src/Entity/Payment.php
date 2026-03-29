<?php

namespace App\Entity;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\PaymentStatus;
use App\Repository\PaymentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represents a payment made by a user for a subscription or other services.
 *
 * Stores amount, discounts, currency, status, transaction details, timestamps,
 * and associations to the user and subscription.
 */
class Payment
{
    /**
     * The unique identifier of the payment.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * The final amount paid by the user (after discounts).
     *
     * @var float|null
     */
    private ?float $amount = null;

    /**
     * Original amount before discounts.
     *
     * @var float|null
     */
    private ?float $baseAmount = null;

    /**
     * The discount applied to the payment.
     *
     * @var float|null
     */
    private ?float $discountApplied = null;

    /**
     * Currency code (e.g., USD, EUR).
     *
     * @var string|null
     */
    private ?string $currency = null;

    /**
     * Payment status (e.g., pending, success, failed).
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Transaction identifier from payment gateway.
     *
     * @var string|null
     */
    private ?string $transactionId = null;

    /**
     * Payment method (e.g., card, PayPal).
     *
     * @var string|null
     */
    private ?string $paymentMethod = null;

    /**
     * Reason for payment failure, if any.
     *
     * @var string|null
     */
    private ?string $failureReason = null;

    /**
     * Timestamp of payment creation.
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
     * The user who made the payment.
     *
     * @var User|null
     */
    private ?User $user = null;

    /**
     * The subscription associated with this payment.
     *
     * @var Subscription|null
     */
    private ?Subscription $subscription = null;

    /**
     * The invoice associated with this payment.
     *
     * @var Subscription|null
     */
    private ?Invoice $invoice = null;

    /**
     * Constructor.
     *
     * Initializes creation timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->status = PaymentStatus::PENDING->value;
    }

    /**
     * Get the payment ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the final amount paid.
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Set the final amount paid.
     *
     * @param float $amount
     * @return static
     */
    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get the base amount before discounts.
     *
     * @return float|null
     */
    public function getBaseAmount(): ?float
    {
        return $this->baseAmount;
    }

    /**
     * Set the base amount before discounts.
     *
     * @param float|null $baseAmount
     * @return static
     */
    public function setBaseAmount(?float $baseAmount): static
    {
        $this->baseAmount = $baseAmount;
        return $this;
    }

    /**
     * Get the discount applied.
     *
     * @return float|null
     */
    public function getDiscountApplied(): ?float
    {
        return $this->discountApplied;
    }

    /**
     * Set the discount applied.
     *
     * @param float|null $discountApplied
     * @return static
     */
    public function setDiscountApplied(?float $discountApplied): static
    {
        $this->discountApplied = $discountApplied;
        return $this;
    }

    /**
     * Get the currency code.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Set the currency code.
     *
     * @param string|null $currency
     * @return static
     */
    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get the payment status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the payment status.
     *
     * @param string $status
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get the transaction ID from the gateway.
     *
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * Set the transaction ID.
     *
     * @param string|null $transactionId
     * @return static
     */
    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Get the payment method used.
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * Set the payment method.
     *
     * @param string $paymentMethod
     * @return static
     */
    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * Get the failure reason.
     *
     * @return string|null
     */
    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    /**
     * Set the failure reason.
     *
     * @param string $failureReason
     * @return static
     */
    public function setFailureReason(string $failureReason): static
    {
        $this->failureReason = $failureReason;
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
     * Get the last update timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the last update timestamp.
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
     * Get the user who made the payment.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the user who made the payment.
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
     * Get the associated subscription.
     *
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     * Set the associated subscription.
     *
     * @param Subscription|null $subscription
     * @return static
     */
    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): static
    {
        // set the owning side of the relation if necessary
        if ($invoice->getPayment() !== $this) {
            $invoice->setPayment($this);
        }

        $this->invoice = $invoice;

        return $this;
    }

}