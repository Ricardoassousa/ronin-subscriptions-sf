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
            $this->logger->error('Cannot subscribe: user is null.');
            throw new InvalidArgumentException('User cannot be null.');
        }

        if (!$subscriptionPlan) {
            $this->logger->error('Cannot subscribe: subscription plan is null.');
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
        $subscription->setStatus(SubscriptionStatus::ACTIVE->value);
        $subscription->setStartedAt(new DateTimeImmutable());
        $subscription->setNextBillingAt(new DateTimeImmutable('+1 month'));

        $this->em->persist($subscription);
        $this->em->flush();

        $this->logger->info('User subscribed to a plan', [
            'user_id' => $user->getId(),
            'subscription_plan_id' => $subscriptionPlan->getId(),
            'subscription_id' => $subscription->getId()
        ]);

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
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan): void
    {
        if (!$subscription) {
            $this->logger->error('Cannot change subscription plan: subscription is null.');
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if (!$newPlan) {
            $this->logger->error('Cannot change subscription plan: new plan is null.');
            throw new InvalidArgumentException('New subscription plan cannot be null.');
        }

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED) {
            $this->logger->warning('Cannot change subscription plan: subscription is cancelled', [
                'subscription_id' => $subscription->getId()
            ]);
            throw new LogicException('Cannot change plan of a cancelled subscription.');
        }

        $oldPlanId = $subscription->getSubscriptionPlan()?->getId();
        $subscription->setSubscriptionPlan($newPlan);
        $subscription->setNextBillingAt(new DateTimeImmutable('+1 month'));

        $this->em->flush();

        $this->logger->info('Subscription plan changed', [
            'subscription_id' => $subscription->getId(),
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $newPlan->getId()
        ]);
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
            $this->logger->error('Cannot cancel: subscription is null.');
            throw new InvalidArgumentException('Subscription cannot be null.');
        }

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED->value) {
            $this->logger->warning('Cannot cancel: subscription already cancelled', [
                'subscription_id' => $subscription->getId()
            ]);
            throw new LogicException('Subscription is already cancelled.');
        }

        $subscription->setStatus(SubscriptionStatus::CANCELLED->value);
        $subscription->setEndsAt($subscription->getNextBillingAt());
        $subscription->setCancelledAt(new DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Subscription cancelled', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $subscription->getUser()?->getId()
        ]);
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

        if ($subscription->getStatus() !== SubscriptionStatus::ACTIVE) {
            $this->logger->warning('Cannot pause: subscription not active', [
                'subscription_id' => $subscription->getId(),
                'current_status' => $subscription->getStatus()
            ]);
            throw new LogicException('Only active subscriptions can be paused.');
        }

        $subscription->setStatus(SubscriptionStatus::PAUSED);
        $subscription->setPausedAt(new DateTimeImmutable());

        $this->em->flush();

        $this->logger->info('Subscription paused', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $subscription->getUser()?->getId()
        ]);
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

        if ($subscription->getStatus() !== SubscriptionStatus::PAUSED) {
            $this->logger->warning('Cannot resume: subscription not paused', [
                'subscription_id' => $subscription->getId(),
                'current_status' => $subscription->getStatus()
            ]);
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

        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setPausedAt(null);

        $this->em->flush();

        $this->logger->info('Subscription resumed', [
            'subscription_id' => $subscription->getId(),
            'user_id' => $subscription->getUser()?->getId()
        ]);
    }

}