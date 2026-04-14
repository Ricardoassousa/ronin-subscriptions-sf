<?php

namespace App\Enum;

/**
 * Enum representing the different states of a subscription.
 *
 * - ACTIVE: The subscription is currently active.
 * - CANCELLED: The subscription has been cancelled by the user or admin.
 * - EXPIRED: The subscription period has ended without renewal.
 * - PAUSED: The subscription is temporarily suspended (paused).
 * - PAST_DUE: Payment failed, but the subscription is still recoverable.
 * - PENDING_PAYMENT: Payment is pending; subscription not yet active.
 *
 * This enum is used in the Subscription entity to track lifecycle state.
 */
enum SubscriptionStatus: string
{
    /**
     * The subscription is currently active and in good standing.
     */
    case ACTIVE = 'active';

    /**
     * The subscription was cancelled before the end of the billing period.
     */
    case CANCELLED = 'cancelled';

    /**
     * The subscription period has ended and was not renewed.
     */
    case EXPIRED = 'expired';

    /**
     * The subscription is temporarily suspended (e.g., paused by the user).
     */
    case PAUSED = 'paused';

    /**
     * Payment failed, but the subscription is still recoverable.
     */
    case PAST_DUE = 'past_due';

    /**
     * Payment is pending; subscription not yet active.
     */
    case PENDING_PAYMENT = 'pending';

    /**
     * Check if the subscription is currently active.
     *
     * @return bool True if status is ACTIVE
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the subscription is currently cancelled.
     *
     * @return bool True if status is CANCELLED
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Check if the subscription has expired.
     *
     * @return bool True if status is EXPIRED
     */
    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    /**
     * Check if the subscription is currently paused.
     *
     * @return bool True if status is PAUSED
     */
    public function isPaused(): bool
    {
        return $this === self::PAUSED;
    }

    /**
     * Check if the subscription is past due (payment failed).
     */
    public function isPastDue(): bool
    {
        return $this === self::PAST_DUE;
    }

    /**
     * Check if the subscription is pending payment.
     */
    public function isPendingPayment(): bool
    {
        return $this === self::PENDING_PAYMENT;
    }

}