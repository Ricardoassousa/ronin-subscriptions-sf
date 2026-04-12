<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Enum\SubscriptionStatus;
use App\Service\CustomerProfileService;
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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Throwable;

class PaymentController extends AbstractController
{
    /**
     * Displays the payment history for the currently logged-in user with pagination.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param CustomerProfileService $customerProfileService
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function history(Request $request, EntityManagerInterface $em, CustomerProfileService $customerProfileService, PaginatorInterface $paginator, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        $logger->info(
            'User accessed payment history.',
            [
                'user_id' => $user?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        $query = $em->getRepository(Payment::class)->findPaymentsByUser($user);

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
    public function process(Request $request, EntityManagerInterface $em, PaymentService $paymentService, InvoiceService $invoiceService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): JsonResponse
    {
        $paymentId = (int) $request->get('paymentId', 0);

        if (!$paymentId) {
            return $this->json([
                'status' => PaymentStatus::FAILED->value,
                'reason' => 'Missing paymentId'
            ], 400);
        }

        $payment = $em->getRepository(Payment::class)->find($paymentId);

        if (!$payment || $payment->getUser()?->getId() !== $user?->getId()) {
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

            $result = $paymentService->process($amount);
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
                            'payment_id' => $payment->getId(),
                            'exception' => $e,
                            'source' => [
                                'method' => __METHOD__,
                                'line' => __LINE__
                            ]
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
                    'payment_id' => $paymentId,
                    'exception' => $e,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
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
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function checkout(int $paymentId, Request $request, EntityManagerInterface $em, PaymentService $paymentService, InvoiceService $invoiceService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        $payment = $em->getRepository(Payment::class)->find($paymentId);
        if (!$payment || $payment->getUser()?->getId() !== $user?->getId()) {
            $this->addFlash('danger', 'Payment not found or unauthorized.');
            return $this->redirectToRoute('subscription_index');
        }

        // Check permission with PaymentVoter
        if (!$authChecker->isGranted('PAYMENT_VIEW', $payment)) {
            $this->addFlash('danger', 'You are not authorized to view or process this payment.');
            return $this->redirectToRoute('payments_history');
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
                    $subscription = $payment->getSubscription();
                    if ($subscription->getStatus() === SubscriptionStatus::PENDING_PAYMENT->value) {
                        $subscription->setStatus(SubscriptionStatus::ACTIVE->value);
                        $em->flush();
                    }

                    try {
                        $invoice = $invoiceService->generate($payment);
                        $invoiceNumber = $invoice?->getInvoiceNumber();
                        $this->addFlash('success', 'Payment successful! Invoice generated: ' . $invoiceNumber);
                    } catch (Throwable $e) {
                        $invoiceNumber = null;
                        $this->logger->error(
                            'Failed to generate invoice for successful payment.',
                            [
                                'payment_id' => $payment->getId(),
                                'exception' => $e,
                                'source' => [
                                    'method' => __METHOD__,
                                    'line' => __LINE__
                                ]
                            ]
                        );
                    }
                } else {
                    $this->addFlash('danger', 'Payment failed: ' . ($result['reason'] ?? 'Unknown'));
                    return $this->redirectToRoute('subscription_index');
                }

                return $this->redirectToRoute('payment_success', ['paymentId' => $payment->getId()]);

            } catch (Throwable $e) {
                $logger->error(
                    'Payment processing error.',
                    [
                        'payment_id' => $payment->getId(),
                        'exception' => $e,
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
                $this->addFlash('danger', 'Unexpected error occurred during payment.');
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
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function success(int $paymentId, EntityManagerInterface $em, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        try {
            $payment = $em->getRepository(Payment::class)->find($paymentId);

            if (!$payment || $payment->getUser()?->getId() !== $user?->getId()) {
                $logger->warning(
                    'Payment not found or unauthorized access',
                    [
                        'user_id' => $user?->getId(),
                        'payment_id' => $paymentId,
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
                $this->addFlash('danger', 'Payment not found or unauthorized.');
                return $this->redirectToRoute('subscription_index');
            }

            // Check if the user has permission to view the payment
            if (!$authChecker->isGranted('PAYMENT_VIEW', $payment)) {
                $this->addFlash('danger', 'You are not authorized to view this payment.');
                return $this->redirectToRoute('payments_history');
            }

            if ($payment->getStatus() !== PaymentStatus::SUCCESS->value) {
                $logger->info(
                    'Payment was not successful',
                    [
                        'user_id' => $user?->getId(),
                        'payment_id' => $paymentId,
                        'status' => $payment->getStatus(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
                $this->addFlash('danger', 'Payment was not successful.');
                return $this->redirectToRoute('payments_history');
            }

            $invoiceNumber = $payment->getInvoice()->getInvoiceNumber();

            $logger->info(
                'Payment success page accessed',
                [
                    'user_id' => $user?->getId(),
                    'payment_id' => $paymentId,
                    'invoice_number' => $invoiceNumber,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
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
                    'payment_id' => $paymentId,
                    'exception' => $e,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'An unexpected error occurred.');
            return $this->redirectToRoute('subscription_index');
        }
    }

}