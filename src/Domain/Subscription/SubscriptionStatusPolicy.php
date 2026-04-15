<?php

namespace App\Domain\Subscription;

use DateTimeImmutable;
use App\Enum\SubscriptionStatus;

class SubscriptionStatusPolicy
{
    /**
     * Determines if a subscription should be marked as expired.
     *
     * A subscription is considered expired when:
     * - It has a next billing date
     * - The next billing date is in the past (strictly before now)
     *
     * @param DateTimeImmutable $now
     * @param DateTimeImmutable|null $nextBillingAt
     * @return bool
     */
    public function shouldExpire(DateTimeImmutable $now, ?DateTimeImmutable $nextBillingAt): bool
    {
        if ($nextBillingAt === null) {
            return false;
        }

        return $nextBillingAt < $now;
    }

    /**
     * Optional helper: determines if a subscription is active.
     * Useful for keeping business rules centralized.
     */
    public function isActive(string $status): bool
    {
        return $status === SubscriptionStatus::ACTIVE->value;
    }

    /**
     * Optional helper: determines if a subscription is already expired.
     */
    public function isExpired(string $status): bool
    {
        return $status === SubscriptionStatus::EXPIRED->value;
    }

}