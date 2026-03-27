<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class PaymentController extends AbstractController
{
    private PaymentService $paymentService;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        PaymentService $paymentService,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->paymentService = $paymentService;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Internal endpoint to process payment.
     *
     * POST /api/payments/process
     *
     * Request JSON:
     * {
     *     "amount": 50.0
     * }
     *
     * Response JSON (success):
     * {
     *     "status": "success",
     *     "transaction_id": "txn_604f6f0c1c13b2.12345678"
     * }
     *
     * Response JSON (failure):
     * {
     *     "status": "failed",
     *     "reason": "Insufficient funds"
     * }
     */
    public function process(Request $request): JsonResponse
    {
        try {
            $amount = (float) $request->get('amount', 0);

            if ($amount <= 0) {
                $this->logger->warning('Payment attempt with invalid amount.', ['amount' => $amount]);
                return $this->json([
                    'status' => PaymentStatus::FAILED->value,
                    'reason' => 'Invalid amount'
                ], 400);
            }

            // Call the service to process the payment
            $result = $this->paymentService->process($amount);

            // Create and persist the Payment entity
            $payment = new Payment();
            $payment->setAmount($amount)
                    ->setStatus($result['status'])
                    ->setTransactionId($result['transaction_id'] ?? null);

            $this->em->persist($payment);
            $this->em->flush();

            return $this->json($result, $result['status'] === PaymentStatus::SUCCESS->value ? 200 : 400);

        } catch (Throwable $e) {
            $this->logger->error('Unexpected error in PaymentController.', [
                'exception' => $e,
                'request_data' => $request->request->all()
            ]);

            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Internal server error'
            ], 500);
        }
    }

}