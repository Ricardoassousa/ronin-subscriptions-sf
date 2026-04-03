<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Service responsible for sending transactional emails
 * such as subscription confirmations and other customer notifications.
 */
class EmailNotificationService
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * EmailNotificationService constructor.
     *
     * @param MailerInterface $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Sends a subscription confirmation email to the customer.
     *
     * @param Subscription $subscription
     */
    public function sendSubscriptionConfirmation(Subscription $subscription): void
    {
        $user = $subscription->getUser();

        if (!$user || !$user->getEmail()) {
            $this->logger->warning(
                'Missing user/email for subscription.',
                [
                    'subscription_id' => $subscription->getId()
                ]
            );
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from('no-reply@mystore.com')
                ->to($user->getEmail())
                ->subject('Your Subscription Confirmation #' . $subscription->getId())
                ->htmlTemplate('emails/subscription_confirmation.html.twig')
                ->context([
                    'subscription' => $subscription
                ]);

            $this->mailer->send($email);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                'Subscription confirmation email failed to send.',
                [
                    'subscription' => $subscription->getId(),
                    'user_email' => $subscription->getUser()->getEmail(),
                    'exception' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Sends a renewal reminder email to the customer.
     *
     * This notifies the user that their subscription will renew soon.
     *
     * @param Subscription $subscription
     * @return void
     */
    public function sendRenewalReminder(Subscription $subscription): void
    {
        $user = $subscription->getUser();

        if (!$user || !$user->getEmail()) {
            $this->logger->warning(
                'Missing user/email for renewal reminder.',
                [
                    'subscription_id' => $subscription->getId()
                ]
            );
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from('no-reply@mystore.com')
                ->to($user->getEmail())
                ->subject('Your subscription will renew soon')
                ->htmlTemplate('emails/renewal_reminder.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'nextBillingAt' => $subscription->getNextBillingAt()
                ]);

            $this->mailer->send($email);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                'Failed to send renewal reminder email.',
                [
                    'subscription_id' => $subscription->getId(),
                    'user_email' => $user->getEmail(),
                    'exception' => $e->getMessage()
                ]
            );
        }
    }

}