<?php

use App\Service\PaymentService;
use App\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new PaymentService($this->logger);
    }

    public function testProcessReturnsSuccessForValidAmount(): void
    {
        $this->logger->expects($this->once())->method('info');

        $result = $this->service->process(100.0);

        $this->assertEquals(PaymentStatus::SUCCESS->value, $result['status']);
        $this->assertArrayHasKey('transaction_id', $result);
    }

    public function testProcessReturnsFailedForInvalidAmount(): void
    {
        $this->logger->expects($this->once())->method('warning');

        $result = $this->service->process(0.0);

        $this->assertEquals(PaymentStatus::FAILED->value, $result['status']);
        $this->assertEquals('Invalid amount', $result['reason']);
    }

}