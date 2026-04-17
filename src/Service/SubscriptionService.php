<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use LogicException;

/**
 * Service responsible for managing subscriptions.
 *
 * Handles creating, updating, cancelling, pausing, and resuming subscriptions.
 * Includes validations, logging, and exceptions for invalid operations.
 */
class SubscriptionService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SubscriptionService constructor.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Subscribe a user to a subscription plan.
     *
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return Subscription
     * @throws InvalidArgumentException if $user or $subscriptionPlan is null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function subscribe(User $user, SubscriptionPlan $subscriptionPlan): Subscription
    {
        if (!$user) {
            $this->logger->error(
                'Cannot subscribe: user is null.',
                [
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new InvalidArgumentException('User cannot be null.');
        }

        if (!$subscriptionPlan) {
            $this->logger->error(
                'Cannot subscribe: subscription plan is null.',
                [
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new InvalidArgumentException('Subscription plan cannot be null.');
        }

        // Create a new subscription (logic same as original)
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setSubscriptionPlan($subscriptionPlan);
        $subscription->setPriceSnapshot($subscriptionPlan->getPrice());
        $subscription->setCurrencySnapshot($subscriptionPlan->getCurrency());
        $subscription->setBillingIntervalSnapshot($subscriptionPlan->getBillingInterval());
        $subscription->setDiscountPercentSnapshot($subscriptionPlan->getDiscountPercent());
        $subscription->setStatus(SubscriptionStatus::PENDING_PAYMENT->value);
        $subscription->setStartedAt(new DateTimeImmutable());

        $trialDays = $subscriptionPlan->getTrialDays() ?? 0;
        $trialEndsAt = null;
        $now = new DateTimeImmutable();
        if ($trialDays > 0) {
            $trialEndsAt = $now->modify("+{$trialDays} days");
            $subscription->setTrialEndsAt($trialEndsAt);
        }

        $interval = match (strtolower($subscriptionPlan->getBillingInterval())) {
            'month' => '1 month',
            'year' => '1 year',
            default => '1 month'
        };

        if ($trialEndsAt) {
            $subscription->setNextBillingAt($trialEndsAt->modify("+$interval"));
        } else {
            $subscription->setNextBillingAt($now->modify("+$interval"));
        }

        $this->em->persist($subscription);
        $this->em->flush();

        $this->logger->info(
            'User subscribed to a plan',
            [
                'user_id' => $user->getId(),
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'subscription_id' => $subscription->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        return $subscription;
    }

    /**
     * Change the subscription plan for an existing subscription.
     *
     * @param Subscription $subscription
     * @param SubscriptionPlan $newPlan
     * @return void
     * @throws InvalidArgumentException if $subscription or $newPlan is null
     * @throws LogicException if subscription is cancelled
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan): Subscription
    {
        if (!$subscription) {
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if (!$newPlan) {
            throw new InvalidArgumentException('New subscription plan cannot be null.');
        }

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED->value) {
            throw new LogicException('Cannot change plan of a cancelled subscription.');
        }

        // Cancel the current subscription
        $subscription->setStatus(SubscriptionStatus::CANCELLED->value);
        $subscription->setEndsAt(new DateTimeImmutable());
        $subscription->setCancelledAt(new DateTimeImmutable());

        // Create a new subscription with the new plan
        $newSubscription = new Subscription();
        $newSubscription->setUser($subscription->getUser());
        $newSubscription->setSubscriptionPlan($newPlan);
        $newSubscription->setPriceSnapshot($newPlan->getPrice());
        $newSubscription->setCurrencySnapshot($newPlan->getCurrency());
        $newSubscription->setBillingIntervalSnapshot($newPlan->getBillingInterval());
        $newSubscription->setDiscountPercentSnapshot($newPlan->getDiscountPercent());
        $newSubscription->setStatus(SubscriptionStatus::ACTIVE->value);
        $newSubscription->setStartedAt(new DateTimeImmutable());

        $now = new DateTimeImmutable();
        $interval = match (strtolower($newPlan->getBillingInterval())) {
            'month' => '1 month',
            'year' => '1 year',
            default => '1 month'
        };
        $subscription->setNextBillingAt($now->modify("+$interval"));

        $this->em->persist($subscription);
        $this->em->persist($newSubscription);
        $this->em->flush();

        $this->logger->info(
            'Subscription plan changed',
            [
                'old_subscription_id' => $subscription->getId(),
                'new_subscription_id' => $newSubscription->getId(),
                'old_plan_id' => $subscription->getSubscriptionPlan()?->getId(),
                'new_plan_id' => $newPlan->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        return $newSubscription;
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @return void
     * @throws InvalidArgumentException if $subscription is null
     * @throws LogicException if subscription is already cancelled
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function cancel(Subscription $subscription): void
    {
        if (!$subscription) {
            $this->logger->error(
                'Cannot cancel: subscription is null.',
                [
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED->value) {
            $this->logger->warning(
                'Cannot cancel: subscription already cancelled',
                [
                    'subscription_id' => $subscription->getId(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new LogicException('Subscription is already cancelled.');
        }

        $subscription->setStatus(SubscriptionStatus::CANCELLED->value);
        $subscription->setEndsAt($subscription->getNextBillingAt());
        $subscription->setCancelledAt(new DateTimeImmutable());
        $subscription->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        $this->logger->info(
            'Subscription cancelled',
            [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );
    }

    /**
     * Pause a subscription.
     *
     * @param Subscription $subscription
     * @return void
     * @throws InvalidArgumentException if $subscription is null
     * @throws LogicException if subscription is not active
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function pause(Subscription $subscription): void
    {
        if (!$subscription) {
            $this->logger->error('Cannot pause: subscription is null.');
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if ($subscription->getStatus() !== SubscriptionStatus::ACTIVE->value) {
            $this->logger->warning(
                'Cannot pause: subscription not active',
                [
                    'subscription_id' => $subscription->getId(),
                    'current_status' => $subscription->getStatus(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new LogicException('Only active subscriptions can be paused.');
        }

        $subscription->setStatus(SubscriptionStatus::PAUSED->value);
        $subscription->setPausedAt(new DateTimeImmutable());
        $subscription->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        $this->logger->info(
            'Subscription paused',
            [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );
    }

    /**
     * Resume a paused subscription and adjust the next billing date.
     *
     * @param Subscription $subscription
     * @return void
     * @throws InvalidArgumentException if $subscription is null
     * @throws LogicException if subscription is not paused
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function resume(Subscription $subscription): void
    {
        if (!$subscription) {
            $this->logger->error('Cannot resume: subscription is null.');
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if ($subscription->getStatus() !== SubscriptionStatus::PAUSED->value) {
            $this->logger->warning(
                'Cannot resume: subscription not paused',
                [
                    'subscription_id' => $subscription->getId(),
                    'current_status' => $subscription->getStatus(),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );
            throw new LogicException('Only paused subscriptions can be resumed.');
        }

        $pausedAt = $subscription->getPausedAt();
        $now = new DateTimeImmutable();

        if ($pausedAt) {
            $interval = $pausedAt->diff($now);
            $nextBilling = $subscription->getNextBillingAt();
            if ($nextBilling) {
                $subscription->setNextBillingAt($nextBilling->add($interval));
            }
        }

        $subscription->setStatus(SubscriptionStatus::ACTIVE->value);
        $subscription->setPausedAt(null);
        $subscription->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        $this->logger->info(
            'Subscription resumed',
            [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()?->getId(),
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );
    }

}