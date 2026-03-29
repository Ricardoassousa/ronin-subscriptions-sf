<?php

namespace App\Enum;

/**
 * Enum representing the different states of a payment.
 *
 * - PENDING: Payment is created but not yet completed.
 * - SUCCESS: Payment has been successfully processed.
 * - FAILED: Payment attempt failed.
 * - REFUNDED: Payment has been refunded to the user.
 *
 * This enum is used in the Payment entity to track payment lifecycle.
 */
enum PaymentStatus: string
{
    /**
     * Payment is created and awaiting processing.
     */
    case PENDING = 'pending';

    /**
     * Payment has been successfully processed.
     */
    case SUCCESS = 'success';

    /**
     * Payment attempt failed (e.g., declined, error).
     */
    case FAILED = 'failed';

    /**
     * Payment has been refunded to the user.
     */
    case REFUNDED = 'refunded';

    /**
     * Check if payment is pending.
     *
     * @return bool True if status is PENDING
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if payment was successful.
     *
     * @return bool True if status is SUCCESS
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if payment failed.
     *
     * @return bool True if status is FAILED
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Check if payment was refunded.
     *
     * @return bool True if status is REFUNDED
     */
    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

}