<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Payment;
use App\Enum\ActivityLogType;
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
        $logger->info(
            'User accessed payment history.',
            [
                'user_id' => $this->getUser()?->getId()
            ]
        );

        $query = $em->getRepository(Payment::class)
                    ->createQueryBuilder('p')
                    ->where('p.user = :user')
                    ->setParameter('user', $this->getUser())
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery();

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
    public function process(Request $request, EntityManagerInterface $em, PaymentService $paymentService, InvoiceService $invoiceService, LoggerInterface $logger): JsonResponse
    {
        $paymentId = (int) $request->get('paymentId', 0);

        if (!$paymentId) {
            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Missing paymentId'
            ], 400);
        }

        $payment = $em->getRepository(Payment::class)->find($paymentId);

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

            $result = $this->paymentService->process($amount);
            $payment->setStatus($result['status']);
            $payment->setTransactionId($result['transaction_id']);
            $em->flush();

            // Generate invoice if payment succeeded
            if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
                try {
                    $invoice = $invoiceService->generate($payment);
                    $invoiceNumber = $invoice?->getInvoiceNumber();
                } catch (Throwable $e) {
                    $invoiceNumber = null;
                    $logger->error(
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
            $logger->error(
                'Unexpected error processing payment.',
                [
                    'exception' => $e,
                    'payment_id' => $paymentId
                ]
            );

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
     * @param EntityManagerInterface $em
     * @param PaymentService $paymentService
     * @param InvoiceService $invoiceService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function checkout(int $paymentId, Request $request, EntityManagerInterface $em, PaymentService $paymentService, InvoiceService $invoiceService, LoggerInterface $logger): Response
    {
        $payment = $em->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getUser()?->getId() !== $this->getUser()?->getId()) {
            $this->addFlash('error', 'Payment not found or unauthorized.');
            return $this->redirectToRoute('subscription_index');
        }

        if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
            return $this->redirectToRoute('payment_success', ['paymentId' => $payment->getId()]);
        }

        if ($request->isMethod('POST')) {
            try {
                $result = $paymentService->process($payment->getAmount());

                $payment->setStatus($result['status']);
                $payment->setTransactionId($result['transaction_id']);
                $em->flush();

                // Generate invoice if successful
                if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
                    try {
                        $invoice = $invoiceService->generate($payment);
                        $invoiceNumber = $invoice?->getInvoiceNumber();
                        $this->addFlash('success', 'Payment successful! Invoice generated: ' . $invoiceNumber);
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
                } else {
                    $this->addFlash('error', 'Payment failed: ' . ($result['reason'] ?? 'Unknown'));
                    return $this->redirectToRoute('subscription_index');
                }

                return $this->redirectToRoute('payment_success', ['paymentId' => $payment->getId()]);

            } catch (Throwable $e) {
                $logger->error(
                    'Payment processing error.',
                    [
                        'exception' => $e,
                        'payment_id' => $payment->getId()
                    ]
                );
                $this->addFlash('error', 'Unexpected error occurred during payment.');
                return $this->redirectToRoute('subscription_index');
            }
        }

        return $this->render('payment/checkout.html.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * Displays the success page for a successful payment.
     *
     * Retrieves the payment details and renders the success page. If the payment is not found
     * or the user is not authorized, redirects to the subscription page. If the payment status
     * is not "SUCCESS", redirects to the payments history.
     *
     * @param int $paymentId
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function success(int $paymentId, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        try {
            $payment = $em->getRepository(Payment::class)->find($paymentId);

            if (!$payment || $payment->getUser()?->getId() !== $this->getUser()?->getId()) {
                $logger->warning(
                    'Payment not found or unauthorized access',
                    [
                        'user_id' => $this->getUser()?->getId(),
                        'payment_id' => $paymentId
                    ]
                );
                $this->addFlash('error', 'Payment not found or unauthorized.');
                return $this->redirectToRoute('subscription_index');
            }

            if ($payment->getStatus() !== PaymentStatus::SUCCESS->value) {
                $logger->info(
                    'Payment was not successful',
                    [
                        'user_id' => $this->getUser()?->getId(),
                        'payment_id' => $paymentId,
                        'status' => $payment->getStatus()
                    ]
                );
                $this->addFlash('error', 'Payment was not successful.');
                return $this->redirectToRoute('payments_history');
            }

            $invoiceNumber = $payment->getInvoice()->getInvoiceNumber();

            $logger->info(
                'Payment success page accessed',
                [
                    'user_id' => $this->getUser()?->getId(),
                    'payment_id' => $paymentId,
                    'invoice_number' => $invoiceNumber
                ]
            );

            return $this->render('payment/success.html.twig', [
                'payment' => $payment,
                'invoiceNumber' => $invoiceNumber
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Unexpected error accessing payment success page',
                [
                    'exception' => $e,
                    'payment_id' => $paymentId
                ]
            );

            $this->addFlash('error', 'An unexpected error occurred.');
            return $this->redirectToRoute('subscription_index');
        }
    }

}