<?php

namespace App\Command;

use App\Entity\Subscription;
use App\Service\EmailNotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Command responsible for sending subscription renewal reminders.
 *
 * This command retrieves all subscriptions that are scheduled to renew
 * within the next 3 days and triggers reminder notifications (e.g., email).
 *
 * Typically executed via a cron job (e.g., daily).
 */
class SendRenewalRemindersCommand extends Command
{
    protected static $defaultName = 'app:send-renewal-reminders';
    protected static $defaultDescription = 'Sends renewal reminders to customers within the next 3 days.';

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
     * Constructor.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @param EmailNotificationService $emailNotificationService
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, EmailNotificationService $emailNotificationService)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->emailNotificationService = $emailNotificationService;
    }

    /**
     * Configures the command description.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * Executes the renewal reminder process.
     *
     * This method finds all subscriptions that will renew within the next
     * 3 days and processes them for notification (e.g., email sending).
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int Returns Command::SUCCESS on success, or Command::FAILURE on error.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = new DateTimeImmutable('+3 days');

        try {
            $subscriptions = $this->em->getRepository(Subscription::class)->findRenewingSoon($date);

            if (empty($subscriptions)) {
                $this->logger->info('No subscriptions found for renewal reminders.');
                return Command::SUCCESS;
            }

            foreach ($subscriptions as $subscription) {
                $this->emailNotificationService->sendRenewalReminder($subscription);

                $this->logger->info(
                    'Renewal reminder processed',
                    [
                        'subscription_id' => $subscription->getId(),
                        'user_id' => $subscription->getUser()?->getId(),
                        'next_billing_at' => $subscription->getNextBillingAt()?->format(DATE_ATOM),
                    ]
                );
            }

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to send renewal reminders',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            return Command::FAILURE;
        }
    }

}