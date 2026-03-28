<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Service\InvoiceService;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class PaymentController extends AbstractController
{
    private EntityManagerInterface $em;
    private PaymentService $paymentService;
    private InvoiceService $invoiceService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        PaymentService $paymentService,
        InvoiceService $invoiceService,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->paymentService = $paymentService;
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
    }

    /**
     * Displays the payment history for the currently logged-in user with pagination.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, LoggerInterface $logger): Response
    {
        $this->logger->info(
            'User accessed payment history.',
            [
                'user_id' => $this->getUser()?->getId(),
            ]
        );

        // Retrieve payments for the logged-in user, ordered by creation date descending
        $query = $this->em->getRepository(Payment::class)
                          ->createQueryBuilder('p')
                          ->where('p.user = :user')
                          ->setParameter('user', $this->getUser())
                          ->orderBy('p.createdAt', 'DESC')
                          ->getQuery();

        // Paginate results: 10 items per page
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('payment/history.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * Internal endpoint to process payment for an existing Payment entity.
     *
     * POST /api/payments/process
     *
     * Request JSON:
     * {
     * "paymentId": 123
     * }
     *
     * Response JSON (success):
     * {
     * "status": "success",
     * "transaction_id": "txn_604f6f0c1c13b2.12345678"
     * }
     *
     * Response JSON (failure):
     * {
     * "status": "failed",
     * "reason": "Insufficient funds"
     * }
     */
    public function process(Request $request): JsonResponse
    {
        $paymentId = (int) $request->get('paymentId', 0);

        if (!$paymentId) {
            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Missing paymentId'
            ], 400);
        }

        $payment = $this->em->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getUser()?->getId() !== $this->getUser()?->getId()) {
            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Payment not found or unauthorized'
            ], 404);
        }

        if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
            return $this->json([
                'status' => PaymentStatus::SUCCESS->value,
                'transaction_id' => $payment->getTransactionId(),
                'message' => 'Payment already completed'
            ], 200);
        }

        try {
            $amount = $payment->getAmount();

            if ($amount <= 0) {
                return $this->json([
                    'status' => PaymentStatus::FAILED->value,
                    'reason' => 'Invalid amount'
                ], 400);
            }

            // Process the payment
            $result = $this->paymentService->process($amount);

            // Update the Payment entity
            $payment->setStatus($result['status']);
            $payment->setTransactionId($result['transaction_id']);
            $this->em->flush();

            // Generate invoice if payment succeeded
            if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
                try {
                    $invoice = $this->invoiceService->generate($payment);
                    $invoiceNumber = $invoice?->getInvoiceNumber();
                } catch (Throwable $e) {
                    $invoiceNumber = null;
                    $this->logger->error(
                        'Failed to generate invoice for successful payment.',
                        [
                            'exception' => $e,
                            'payment_id' => $payment->getId()
                        ]
                    );
                }

                return $this->json([
                    'status' => PaymentStatus::SUCCESS->value,
                    'transaction_id' => $payment->getTransactionId(),
                    'invoice_number' => $invoiceNumber,
                    'message' => 'Payment successful'
                ], 200);
            }

            return $this->json([
                'status' => $result['status'],
                'reason' => $result['reason'] ?? 'Payment failed'
            ], 400);

        } catch (Throwable $e) {
            $this->logger->error('Unexpected error processing payment.', [
                'exception' => $e,
                'payment_id' => $paymentId
            ]);

            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handles the checkout process for a specific payment.
     *
     * This method displays the checkout page for the given payment ID.
     * If the request is a POST, it attempts to process the payment
     * and generate an invoice if successful.
     *
     * Access is restricted to the user who owns the payment.
     *
     * @param int $paymentId
     * @param Request $request
     * @return Response
     */
    public function checkout(int $paymentId, Request $request): Response
    {
        $payment = $this->em->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getUser()?->getId() !== $this->getUser()?->getId()) {
            $this->addFlash('error', 'Payment not found or unauthorized.');
            return $this->redirectToRoute('subscription_index');
        }

        // If POST, process payment
        if ($request->isMethod('POST')) {
            try {
                $result = $this->paymentService->process($payment->getAmount());

                $payment->setStatus($result['status']);
                $payment->setTransactionId($result['transaction_id'] ?? null);
                $this->em->flush();

                // Generate invoice if successful
                if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
                    $invoice = $this->invoiceService->generate($payment);
                    $this->addFlash('success', 'Payment successful! Invoice generated: ' . $invoice->getInvoiceNumber());
                } else {
                    $this->addFlash('error', 'Payment failed: ' . ($result['reason'] ?? 'Unknown'));
                }

                return $this->redirectToRoute('payments_history');

            } catch (Throwable $e) {
                $this->logger->error('Payment processing error.', [
                    'exception' => $e,
                    'payment_id' => $payment->getId(),
                ]);
                $this->addFlash('error', 'Unexpected error occurred during payment.');
                return $this->redirectToRoute('subscription_index');
            }
        }

        // Render checkout page
        return $this->render('payment/checkout.html.twig', [
            'payment' => $payment,
        ]);
    }

}