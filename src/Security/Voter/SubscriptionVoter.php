<?php

namespace App\Security\Voter;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter responsible for managing access to Subscription entities.
 *
 * Rules:
 * - Only the owner of a subscription can manage it
 * - Admin users have full access
 * - Specific actions are restricted based on the subscription status
 */
class SubscriptionVoter extends Voter
{
    public const VIEW = 'SUBSCRIPTION_VIEW';
    public const CANCEL = 'SUBSCRIPTION_CANCEL';
    public const PAUSE = 'SUBSCRIPTION_PAUSE';
    public const RESUME = 'SUBSCRIPTION_RESUME';
    public const CHANGE_PLAN = 'SUBSCRIPTION_CHANGE_PLAN';

    public function __construct(
        private Security $security,
        private LoggerInterface $logger
    ) {}

    /**
     * Determines whether the voter supports the given attribute and subject.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::CANCEL,
            self::PAUSE,
            self::RESUME,
            self::CHANGE_PLAN
        ], true) && $subject instanceof Subscription;
    }

    /**
     * Performs the access check for a given attribute on a subscription.
     *
     * @param string $attribute
     * @param Subscription $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $subscription = $subject;

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $owner = $subscription->getUser();

        if (!$owner) {
            $this->logger->log(
                LogLevel::WARNING,
                'Subscription has no associated user.',
                [
                    'subscription_id' => $subscription->getId(),
                    'attempted_by' => $user->getId()
                ]
            );

            return false;
        }

        if ($owner !== $user) {
            $this->logger->log(
                LogLevel::WARNING,
                'Unauthorized subscription access attempt.',
                [
                    'subscription_id' => $subscription->getId(),
                    'owner_id' => $owner->getId(),
                    'attempted_by' => $user->getId(),
                    'action' => $attribute,
                ]
            );

            return false;
        }

        return match ($attribute) {
            self::VIEW => true,

            self::CANCEL, self::PAUSE => $subscription->getStatus() === SubscriptionStatus::ACTIVE->value,

            self::RESUME => $subscription->getStatus() === SubscriptionStatus::PAUSED->value,

            self::CHANGE_PLAN => true,

            default => false
        };
    }

}