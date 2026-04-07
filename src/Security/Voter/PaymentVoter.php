<?php

namespace App\Security\Voter;

use App\Entity\Payment;
use App\Entity\User;
use App\Enum\PaymentStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter responsible for handling authorization logic related to Payment entities.
 *
 * Rules:
 * - Only the owner of the payment can view or process it
 * - Admin users have full access
 * - Payments already marked as SUCCESS cannot be processed again
 */
class PaymentVoter extends Voter
{
    public const VIEW = 'PAYMENT_VIEW';
    public const PROCESS = 'PAYMENT_PROCESS';

    public function __construct(
        private Security $security,
        private LoggerInterface $logger
    ) {}

    /**
     * Determines if this voter supports the given attribute and subject.
     *
     * @param string $attribute The attribute being voted on
     * @param mixed $subject The subject (should be a Payment)
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::PROCESS
        ], true) && $subject instanceof Payment;
    }

    /**
     * Performs the authorization check.
     *
     * @param string $attribute
     * @param Payment $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $payment = $subject;

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $owner = $payment->getUser();

        if (!$owner) {
            $this->logger->log(
                LogLevel::WARNING,
                'Payment has no associated user.',
                [
                    'payment_id' => $payment->getId(),
                    'attempted_by' => $user->getId()
                ]
            );

            return false;
        }

        $isOwner = $owner === $user;

        if (!$isOwner) {
            $this->logger->log(
                LogLevel::WARNING,
                'Unauthorized payment access attempt.',
                [
                    'payment_id' => $payment->getId(),
                    'owner_id' => $owner->getId(),
                    'attempted_by' => $user->getId(),
                    'action' => $attribute
                ]
            );

            return false;
        }

        return match ($attribute) {
            self::VIEW => true,

            self::PROCESS => $this->canProcess($payment, $user),

            default => false
        };
    }

    /**
     * Determines if a payment can be processed.
     *
     * @param Payment $payment
     * @param User $user
     *
     * @return bool
     */
    private function canProcess(Payment $payment, User $user): bool
    {
        if ($payment->getStatus() === PaymentStatus::SUCCESS->value) {
            $this->logger->log(
                LogLevel::NOTICE,
                'Attempt to re-process an already successful payment.',
                [
                    'payment_id' => $payment->getId(),
                    'user_id' => $user->getId(),
                ]
            );

            return false;
        }

        if ($payment->getAmount() <= 0) {
            $this->logger->log(
                LogLevel::WARNING,
                'Invalid payment amount detected during processing.',
                [
                    'payment_id' => $payment->getId(),
                    'amount' => $payment->getAmount(),
                ]
            );

            return false;
        }

        return true;
    }

}