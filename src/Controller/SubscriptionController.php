<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Controller for managing user subscriptions.
 *
 * Provides functionality for subscribing, cancelling, and listing subscriptions.
 */
final class SubscriptionController extends AbstractController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Displays a list or dashboard of the user's subscriptions.
     *
     * @return Response
     */
    public function index(): Response
    {
        $this->logger->info(
            'User accessed subscriptions dashboard', [
                'user_id' => $this->getUser()?->getId(),
            ]
        );

        return $this->render('subscription/index.html.twig', [
            'controller_name' => 'SubscriptionController',
        ]);
    }

    /**
     * Subscribe the authenticated user to a given plan.
     *
     * @param int $planId The ID of the subscription plan
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     *
     * @return Response
     */
    public function subscribe(int $planId, EntityManagerInterface $em, SubscriptionService $subscriptionService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->logger->warning(
                'Unauthorized subscription attempt',
                [
                    'plan_id' => $planId
                ]
            );
            return $this->redirectToRoute('app_login');
        }

        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($planId);

        if (!$subscriptionPlan) {
            $this->logger->error(
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

            $this->logger->info(
                'User subscribed to plan',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'plan_id' => $planId,
                ]
            );
            $this->addFlash('success', 'You have successfully subscribed!');

        } catch (Throwable $e) {
            $this->logger->error(
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
     * Cancel an active subscription for the authenticated user.
     *
     * @param int $id The ID of the subscription to cancel
     * @param EntityManagerInterface $em
     * @param SubscriptionService $subscriptionService
     *
     * @return Response
     */
    public function cancel(int $id, EntityManagerInterface $em, SubscriptionService $subscriptionService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->logger->warning('Unauthorized cancel attempt', ['subscription_id' => $id]);
            return $this->redirectToRoute('app_login');
        }

        $subscription = $em->getRepository(Subscription::class)->find($id);

        if (!$subscription || $subscription->getUser()?->getId() !== $user->getId()) {
            $this->logger->warning(
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

            $this->logger->info(
                'Subscription cancelled',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId()
                ]
            );
            $this->addFlash('success', 'Subscription cancelled successfully!');

        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to cancel subscription',
                [
                    'user_id' => $user->getId(),
                    'subscription_id' => $id,
                    'exception' => $e->getMessage()
                ]
            );
            dd($e);
            $this->addFlash('error', 'Failed to cancel subscription. Please try again.');
        }

        return $this->redirectToRoute('subscription_index');
    }

}