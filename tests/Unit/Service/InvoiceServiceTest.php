<?php

namespace App\Tests\Service;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Service\InvoiceService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Error;

class InvoiceServiceTest extends TestCase
{
    private InvoiceService $invoiceService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->invoiceService = new InvoiceService(
            $this->entityManager,
            $this->logger
        );
    }

    public function testGenerateReturnsNullIfPaymentNotSuccessful(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getStatus')->willReturn('failed');
        $payment->method('getId')->willReturn(1);

        $result = $this->invoiceService->generate($payment);

        $this->assertNull($result);
    }

    public function testGenerateReturnsExistingInvoiceIfAlreadyPresent(): void
    {
        $payment = $this->createMock(Payment::class);
        $invoice = $this->createMock(Invoice::class);

        $payment->method('getStatus')->willReturn('success');
        $payment->method('getInvoice')->willReturn($invoice);

        $result = $this->invoiceService->generate($payment);

        $this->assertSame($invoice, $result);
    }

    public function testGenerateCreatesInvoiceSuccessfully(): void
    {
        $payment = $this->createMock(Payment::class);
        $subscription = $this->createMock(Subscription::class);

        $payment->method('getStatus')->willReturn('success');
        $payment->method('getInvoice')->willReturn(null);
        $payment->method('getAmount')->willReturn(100.0);
        $payment->method('getCurrency')->willReturn('USD');
        $payment->method('getSubscription')->willReturn($subscription);
        $payment->method('getId')->willReturn(2);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $invoice = $this->invoiceService->generate($payment);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame($payment, $invoice->getPayment());
        $this->assertEquals(100.0, $invoice->getAmount());
        $this->assertEquals('USD', $invoice->getCurrency());
        $this->assertEquals('paid', $invoice->getStatus());
    }

    public function testGenerateThrowsErrorOnPersistFailure(): void
    {
        $payment = $this->createMock(Payment::class);

        $payment->method('getStatus')->willReturn('success');
        $payment->method('getInvoice')->willReturn(null);
        $payment->method('getAmount')->willReturn(100.0);
        $payment->method('getCurrency')->willReturn('USD');
        $payment->method('getSubscription')->willReturn(null);
        $payment->method('getId')->willReturn(3);

        $this->entityManager->method('persist')->willReturnCallback(fn() => null);
        $this->entityManager->method('flush')->willThrowException(new Exception('DB error'));

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class "Doctrine\ORM\ORMException" not found');

        $this->invoiceService->generate($payment);
    }

}