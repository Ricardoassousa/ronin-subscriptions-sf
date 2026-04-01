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
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, LoggerInterface $logger): Response
    {
        $logger->info(
            'User accessed subscriptions dashboard',
            [
                'user_id' => $this->getUser()?->getId(),
            ]
        );

        $currentSubscription = $em->getRepository(Subscription::class)->findOneBy(['user' => $this->getUser()], ['startedAt' => 'DESC']);
        $query = $em->getRepository(SubscriptionPlan::class)
                    ->createQueryBuilder('p')
                    ->where('p.isActive = :active')
                    ->setParameter('active', true)
                    ->orderBy('p.id', 'ASC');

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
     * Subscribe the authenticated user to a given plan.
     *
     * @param int $planId The ID of the subscription plan
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function subscribe(int $planId, EntityManagerInterface $em, SubscriptionService $subscriptionService, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning(
                'Unauthorized subscription attempt',
                [
                    'plan_id' => $planId
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($planId);

        if (!$subscriptionPlan) {
            $logger->error(
                'Subscription plan not found',
                [
                    'plan_id' => $planId
                ]
            );

            $this->addFlash('error', 'Subscription plan not found.');
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

            $this->addFlash('success', 'You have successfully subscribed!');
            return $this->redirectToRoute('payment_checkout', [
                'paymentId' => $payment->getId()
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Subscription failed', [
                    'user_id' => $user->getId(),
                    'plan_id' => $planId,
                    'exception' => $e->getMessage(),
                ]
            );
            $this->addFlash('error', 'Failed to subscribe. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Change the subscription plan for a given subscription.
     *
     * Ensures that:
     * - The user is authenticated
     * - The subscription exists and belongs to the current user
     * - The new plan exists
     *
     * Delegates the business logic to the SubscriptionService.
     *
     * @param int $id The subscription ID
     * @param int $planId The new subscription plan ID
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function changePlan(int $id, int $planId, EntityManagerInterface $em, SubscriptionService $subscriptionService, LoggerInterface $logger): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $logger->warning(
                'Unauthorized plan change attempt',
                [
                    'subscription_id' => $id,
                    'plan_id' => $planId
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
                    'subscription_id' => $id
                ]
            );

            $this->addFlash('error', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        $newPlan = $em->getRepository(SubscriptionPlan::class)->find($planId);

        if (!$newPlan) {
            $logger->error(
                'Subscription plan not found',
                [
                    'plan_id' => $planId
                ]
            );

            $this->addFlash('error', 'Subscription plan not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->changePlan($subscription, $newPlan);

            $logger->info(
                'Subscription plan changed successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'new_plan_id' => $newPlan->getId()
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
                    'exception' => $e->getMessage()
                ]
            );

            $this->addFlash('error', 'Failed to change subscription plan. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Cancel an active subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to cancel
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function cancel(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning('Unauthorized cancel attempt', ['subscription_id' => $id]);
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id
                ]
            );

            $this->addFlash('error', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->cancel($subscription);

            $logger->info(
                'Subscription cancelled',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            );
            $this->addFlash('success', 'Subscription cancelled successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to cancel subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage()
                ]
            );
            $this->addFlash('error', 'Failed to cancel subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Pause an active subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to pause
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function pause(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning(
                'Unauthorized pause attempt',
                [
                    'subscription_id' => $id
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
                    'subscription_id' => $id
                ]
            );

            $this->addFlash('error', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->pause($subscription);

            $logger->info(
                'Subscription paused successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            );
            $this->addFlash('success', 'Subscription paused successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to pause subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage()
                ]
            );
            $this->addFlash('error', 'Failed to pause subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

    /**
     * Resume a paused subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to resume
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function resume(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning('Unauthorized resume attempt', ['subscription_id' => $id]);
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $logger->warning(
                'Subscription not found or does not belong to user',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id
                ]
            );

            $this->addFlash('error', 'Subscription not found.');
            return $this->redirectToRoute('subscription_index');
        }

        try {
            $subscriptionService->resume($subscription);

            $logger->info(
                'Subscription resumed successfully',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            );
            $this->addFlash('success', 'Subscription resumed successfully!');

        } catch (Throwable $e) {
            $logger->error(
                'Failed to resume subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage()
                ]
            );
            $this->addFlash('error', 'Failed to resume subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

}