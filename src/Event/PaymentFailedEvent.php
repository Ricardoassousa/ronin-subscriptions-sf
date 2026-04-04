<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class PaymentFailedEvent
 *
 * This event is dispatched whenever a payment fails.
 * It contains the email of the customer and the reason for failure.
 *
 * Listeners can react to this event to update subscription status,
 * send notifications, or perform logging.
 */
class PaymentFailedEvent extends Event
{
    /**
     * @param string $email
     * @param string $reason
     */
    public function __construct(
        public string $email,
        public string $reason = 'Failed payment'
    ) {}

}