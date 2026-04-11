<?php

namespace App\Service;

use App\Enum\PaymentStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class PaymentService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process a payment through the internal simulation endpoint.
     *
     * @param float $amount The amount to be processed
     * @return array{status: string, transaction_id?: string, reason?: string}
     */
    public function process(float $amount): array
    {
        if ($amount <= 0) {
            $this->logger->warning(
                'Attempted to process invalid payment amount.',
                [
                    'amount' => $amount,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return [
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Invalid amount'
            ];
        }

        try {
            // Simulate processing payment
            $transactionId = 'txn_' . uniqid();

            $this->logger->info(
                'Payment processed successfully.',
                [
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return [
                'status' => PaymentStatus::SUCCESS->value,
                'transaction_id' => $transactionId
            ];
        } catch (Throwable $e) {
            $this->logger->error(
                'Payment processing failed.',
                [
                    'amount' => $amount,
                    'exception' => $e,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return [
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Internal service error'
            ];
        }
    }

}