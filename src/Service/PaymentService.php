<?php

namespace App\Service;

use App\Enum\PaymentStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class PaymentService
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentService constructor.
     *
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
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
        try {
            $response = $this->httpClient->request('POST', '/api/payments/process', [
                'json' => ['amount' => $amount],
            ]);

            $data = $response->toArray();

            // Ensure we return a valid PaymentStatus
            $status = match($data['status'] ?? '') {
                PaymentStatus::SUCCESS->value => PaymentStatus::SUCCESS->value,
                PaymentStatus::FAILED->value  => PaymentStatus::FAILED->value,
                default => PaymentStatus::FAILED->value,
            };

            return [
                'status' => $status,
                'transaction_id' => $data['transaction_id'] ?? null,
                'reason' => $data['reason'] ?? null,
            ];
        } catch (ClientExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            // Log the error
            $this->logger->error(
                'PaymentService error during processing',
                [
                    'amount' => $amount,
                    'exception' => $e
                ]
            );

            return [
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Internal service error'
            ];
        }
    }

}