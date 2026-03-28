<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Payment;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class InvoiceService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentService constructor.
     *
     * @param EntityManagerInterface $logger
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Generates an invoice from a successful payment.
     *
     * This method ensures:
     * - The payment is successful
     * - No duplicate invoice is created
     * - A snapshot of payment data is stored in the invoice
     *
     * @param Payment $payment
     * @return Invoice|null Returns the created invoice or null if not generated
     * @throws ORMException Throws if database persist or flush fails
     */
    public function generate(Payment $payment): ?Invoice
    {
        // Prevent generating invoice for non-successful payments
        if ($payment->getStatus() !== 'success') {
            $this->logger->warning(
                'Invoice generation skipped: payment not successful',
                [
                    'paymentId' => $payment->getId(),
                    'status' => $payment->getStatus()
                ]
            );

            return null;
        }

        // Prevent duplicate invoices
        if ($payment->getInvoice() !== null) {
            $this->logger->info(
                'Invoice already exists for payment',
                [
                    'paymentId' => $payment->getId(),
                    'invoiceId' => $payment->getInvoice()?->getId()
                ]
            );

            return $payment->getInvoice();
        }

        $amount = $payment->getAmount();
        $taxRate = 0.23;

        // Create invoice
        $invoice = new Invoice();
        $invoice->setPayment($payment);
        $invoice->setInvoiceNumber($this->generateInvoiceNumber());
        $invoice->setAmount($amount);
        $invoice->setStatus("paid");
        $invoice->setDescription(sprintf(
            'Subscription payment (Plan ID: %s)',
            $payment->getSubscription()?->getId()
        ));
        $invoice->setDueDate((new DateTimeImmutable())->modify('+7 days'));
        $invoice->setTax(round($amount * $taxRate, 2));
        $invoice->setCurrency($payment->getCurrency());

        // Persist safely
        try {
            $this->em->persist($invoice);
            $this->em->flush();
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to persist invoice to database.',
                [
                    'exception' => $e,
                    'paymentId' => $payment->getId()
                ]
            );
            dd($e);
            throw new ORMException('Invoice persistence failed.');
        }

        $this->logger->info(
            'Invoice generated successfully',
            [
                'paymentId' => $payment->getId(),
                'invoiceId' => $invoice->getId(),
                'invoiceNumber' => $invoice->getInvoiceNumber()
            ]
        );

        return $invoice;
    }

    /**
     * Generates a unique invoice number.
     *
     * Format example: INV-20260328-AB12CD
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        return sprintf(
            'INV-%s-%s',
            (new DateTimeImmutable())->format('Ymd'),
            strtoupper(substr(uniqid(), -6))
        );
    }

}