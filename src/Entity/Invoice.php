<?php

namespace App\Entity;

use App\Entity\Payment;
use App\Repository\InvoiceRepository;
use DateTimeImmutable;

/**
 * Represents an invoice generated for a successful payment.
 *
 * Stores amount, tax, currency, status, description, timestamps,
 * and association to the related Payment entity.
 */
class Invoice
{
    /**
     * The unique identifier of the invoice.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * The unique invoice number.
     *
     * @var string|null
     */
    private ?string $invoiceNumber = null;

    /**
     * Total amount of the invoice.
     *
     * @var float|null
     */
    private ?float $amount = null;

    /**
     * Status of the invoice (e.g., paid, pending).
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Description or notes about the invoice.
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * Currency code (e.g., USD, EUR).
     *
     * @var string|null
     */
    private ?string $currency = null;

    /**
     * Tax amount applied to the invoice.
     *
     * @var float|null
     */
    private ?float $tax = null;

    /**
     * Timestamp of invoice creation.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Due date for payment.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $dueDate = null;

    /**
     * The associated payment.
     *
     * @var Payment|null
     */
    private ?Payment $payment = null;

    /**
     * Constructor.
     *
     * Initializes creation timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Get the invoice ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the invoice number.
     *
     * @return string|null
     */
    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    /**
     * Set the invoice number.
     *
     * @param string $invoiceNumber
     * @return static
     */
    public function setInvoiceNumber(string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    /**
     * Get the total amount.
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Set the total amount.
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
     * Get the invoice status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the invoice status.
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
     * Get the invoice description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the invoice description.
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
     * Get the tax amount.
     *
     * @return float|null
     */
    public function getTax(): ?float
    {
        return $this->tax;
    }

    /**
     * Set the tax amount.
     *
     * @param float|null $tax
     * @return static
     */
    public function setTax(?float $tax): static
    {
        $this->tax = $tax;
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
     * Get the due date.
     *
     * @return DateTimeImmutable|null
     */
    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * Set the due date.
     *
     * @param DateTimeImmutable|null $dueDate
     * @return static
     */
    public function setDueDate(?DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * Get the associated payment.
     *
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * Set the associated payment.
     *
     * @param Payment $payment
     * @return static
     */
    public function setPayment(Payment $payment): static
    {
        $this->payment = $payment;

        // Ensure bidirectional link
        if ($payment->getInvoice() !== $this) {
            $payment->setInvoice($this);
        }

        return $this;
    }

}