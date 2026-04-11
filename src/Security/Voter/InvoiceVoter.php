<?php

namespace App\Security\Voter;

use App\Entity\Invoice;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Voter responsible for handling authorization logic related to Invoice entities.
 *
 * Rules:
 * - Only the owner of the invoice (via Payment -> User) can view it
 * - Admin users have full access
 */
class InvoiceVoter extends Voter
{
    public const VIEW = 'INVOICE_VIEW';

    public function __construct(
        private Security $security,
        private LoggerInterface $logger
    ) {}

    /**
     * Determines if this voter supports the given attribute and subject.
     *
     * @param string $attribute The attribute being voted on
     * @param mixed $subject The subject (should be an Invoice)
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Invoice;
    }

    /**
     * Performs the authorization check.
     *
     * @param string $attribute
     * @param Invoice $invoice
     * @param TokenInterface $token
     * @return bool True if access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $invoice = $subject;

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $owner = $invoice->getPayment()?->getUser();

        if (!$owner) {
            $this->logger->log(
                LogLevel::WARNING,
                'Invoice has no associated payment or user.',
                [
                    'invoice_id' => $invoice->getId(),
                    'user_id' => $user->getId()
                ]
            );

            return false;
        }

        $isOwner = $owner === $user;

        if (!$isOwner) {
            $this->logger->log(
                LogLevel::WARNING,
                'Unauthorized invoice access attempt.',
                [
                    'invoice_id' => $invoice->getId(),
                    'owner_id' => $owner->getId(),
                    'attempted_by' => $user->getId()
                ]
            );
        }

        return $isOwner;
    }
}