<?php

namespace App\EventListener;

use App\Entity\Subscription;
use App\Enum\SubscriptionStatus;
use App\Event\PaymentFailedEvent;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Listener that reacts to payment failure events.
 *
 * - Updating subscription status
 * - Triggering notification emails
 * - Logging the failure
 */
class PaymentFailedListener
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
     * @var EmailNotificationService
     */
    private $emailNotificationService;

    /**
     * PaymentFailedListener constructor.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @param EmailNotificationService $emailNotificationService
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, EmailNotificationService $emailNotificationService)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->emailNotificationService = $emailNotificationService;
    }

    /**
     * Handles the payment failure event.
     *
     * @param PaymentFailedEvent $event
     * @return void
     */
    public function __invoke(PaymentFailedEvent $event): void
    {
        $subscription = $this->em->getRepository(Subscription::class)->findActiveOrPastDueByEmail($event->email);
        if ($subscription === null) {
            $this->logger->warning(
                'Subscription not found for payment failure.',
                [
                    'email' => $event->email
                ]
            );
            return;
        }

        $subscription->setStatus(SubscriptionStatus::PAST_DUE->value);
        $this->em->flush();

        $this->logger->error(
            'Payment failed.',
            [
                'subscription_id' => $subscription->getId(),
                'email' => $event->email,
                'reason' => $event->reason
            ]
        );

        $this->emailNotificationService->sendPaymentFailure(
            $subscription,
            $event->reason
        );
    }

}