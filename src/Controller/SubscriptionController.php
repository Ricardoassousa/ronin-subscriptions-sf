<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Enum\ActivityLogType;
use App\Enum\PaymentStatus;
use App\Enum\SubscriptionStatus;
use App\Repository\SubscriptionRepository;
use App\Service\CustomerProfileService;
use App\Service\EmailNotificationService;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Throwable;

/**
 * Controller for managing user subscriptions.
 *
 * Provides functionality for subscribing, cancelling, and listing subscriptions.
 */
class SubscriptionController extends AbstractController
{
    /**
     * Displays a list or dashboard of the user's subscriptions.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param CustomerProfileService $customerProfileService
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, CustomerProfileService $customerProfileService, PaginatorInterface $paginator, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        $logger->info(
            'User accessed subscriptions dashboard',
            [
                'user_id' => $user?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        $currentSubscription = $em->getRepository(Subscription::class)->findOneBy(['user' => $user], ['startedAt' => 'DESC']);
        $query = $em->getRepository(SubscriptionPlan::class)->findActiveSubscriptionPlans();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('subscription/index.html.twig', [
            'pagination' => $pagination,
            'currentSubscription' => $currentSubscription
        ]);
    }

    /**
     * Displays the subscription history for the currently logged-in user.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param CustomerProfileService $customerProfileService
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function history(Request $request, EntityManagerInterface $em, CustomerProfileService $customerProfileService, PaginatorInterface $paginator, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before viewing subscription history.');
            return $this->redirectToRoute('app_customer_profile');
        }

        $logger->info(
            'User accessed subscription history.',
            [
                'user_id' => $user?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        $query = $em->getRepository(Subscription::class)->findSubscriptionsByUser($user);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('subscription/history.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Subscribe the authenticated user to a given plan.
     *
     * @param int $planId The ID of the subscription plan
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param EmailNotificationService $emailNotificationService
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function subscribe(int $planId, EntityManagerInterface $em, SubscriptionService $subscriptionService, EmailNotificationService $emailNotificationService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        if (!$user) {
            $logger->warning(
                'Unauthorized subscription attempt',
                [
                    'plan_id' => $planId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        // Check if the user has permission to subscribe
        if (!$authChecker->isGranted('SUBSCRIBE_TO_PLAN', $user)) {
            $this->addFlash('danger', 'You are not authorized to subscribe to this plan.');
            return $this->redirectToRoute('subscription_index');
        }

        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($planId);

        if (!$subscriptionPlan) {
            $logger->error(
                'Subscription plan not found',
                [
                    'plan_id' => $planId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription plan not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscription = $subscriptionService->subscribe($user, $subscriptionPlan);

            $logger->info(
                'User subscribed to plan',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'plan_id' => $planId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            // After subscription is created
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setSubscription($subscription);
            $payment->setAmount($subscriptionPlan->getPrice());
            $payment->setCurrency($subscriptionPlan->getCurrency() ?? 'USD');
            $payment->setStatus(PaymentStatus::PENDING->value);
            $em->persist($payment);
            $em->flush();

            $emailNotificationService->sendSubscriptionConfirmation($subscription);

            $this->addFlash('success', 'You have successfully subscribed!');
            return $this->redirectToRoute('payment_checkout', [
                'paymentId' => $payment->getId()
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Subscription failed',
                [
                    'user_id' => $user->getId(),
                    'plan_id' => $planId,
                    'exception' => $e->getMessage(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('danger', 'Failed to subscribe. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Change the subscription plan for a given subscription.
     *
     * @param int $id The subscription ID
     * @param int $planId The new subscription plan ID
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function changePlan(int $id, int $planId, EntityManagerInterface $em, SubscriptionService $subscriptionService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        if (!$user) {
            $logger->warning(
                'Unauthorized plan change attempt',
                [
                    'subscription_id' => $id,
                    'plan_id' => $planId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (
            !$subscription
            || $subscription->getUser()?->getId() !== $user->getId()
        ) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        // Check permission with SubscriptionVoter (Permission to change the plan)
        if (!$authChecker->isGranted('CHANGE_PLAN', $subscription)) {
            $this->addFlash('danger', 'You are not authorized to change this subscription plan.');
            return $this->redirectToRoute('subscription_index');
        }

        $newPlan = $em->getRepository(SubscriptionPlan::class)->find($planId);

        if (!$newPlan) {
            $logger->error(
                'Subscription plan not found',
                [
                    'plan_id' => $planId,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription plan not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->changePlan($subscription, $newPlan);

            $logger->info(
                'Subscription plan changed successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'new_plan_id' => $newPlan->getId(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('success', 'Subscription plan updated successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to change subscription plan',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'plan_id' => $planId,
                    'exception' => $e->getMessage(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Failed to change subscription plan. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Cancel an active subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to cancel
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function cancel(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        if (!$user) {
            $logger->warning(
                'Unauthorized cancel attempt',
                [
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        // Check permission with SubscriptionVoter (Permission to cancel the subscription)
        if (!$authChecker->isGranted('CANCEL_SUBSCRIPTION', $subscription)) {
            $this->addFlash('danger', 'You are not authorized to cancel this subscription.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->cancel($subscription);

            $logger->info(
                'Subscription cancelled',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('success', 'Subscription cancelled successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to cancel subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('danger', 'Failed to cancel subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Pause an active subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to pause
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function pause(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        if (!$user) {
            $logger->warning(
                'Unauthorized pause attempt',
                [
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        // Check permission with SubscriptionVoter (Permission to pause the subscription)
        if (!$authChecker->isGranted('PAUSE_SUBSCRIPTION', $subscription)) {
            $this->addFlash('danger', 'You are not authorized to pause this subscription.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->pause($subscription);

            $logger->info(
                'Subscription paused successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('success', 'Subscription paused successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to pause subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('danger', 'Failed to pause subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Resume a paused subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to resume
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param CustomerProfileService $customerProfileService
     * @param LoggerInterface $logger
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function resume(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, CustomerProfileService $customerProfileService, LoggerInterface $logger, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->getUser();
        if (!$customerProfileService->hasCustomerProfile($user)) {
            $this->addFlash('danger', 'Please complete your customer profile before subscribing.');
            return $this->redirectToRoute('app_customer_profile');
        }

        if (!$user) {
            $logger->warning(
                'Unauthorized resume attempt',
                [
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $this->addFlash('danger', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        // Check permission with SubscriptionVoter (Permission to resume the subscription)
        if (!$authChecker->isGranted('RESUME_SUBSCRIPTION', $subscription)) {
            $this->addFlash('danger', 'You are not authorized to resume this subscription.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->resume($subscription);

            $logger->info(
                'Subscription resumed successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('success', 'Subscription resumed successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to resume subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            $this->addFlash('danger', 'Failed to resume subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

}