<?php

namespace App\Command;

use App\Entity\ActivityLog;
use App\Entity\Subscription;
use App\Enum\ActivityLogType;
use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Command to update subscription statuses.
 *
 * This command checks all active subscriptions and updates their status
 * to EXPIRED if the next billing date has passed.
 */
class UpdateSubscriptionStatusCommand extends Command
{
    protected static $defaultName = 'app:subscriptions:update-status';
    protected static $defaultDescription = 'Updates subscription statuses (active, expired, cancelled).';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    /**
     * Executes the subscription status update.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new DateTimeImmutable();

        try {
            $subscriptions = $this->em->getRepository(Subscription::class)->findBy([
                'status' => SubscriptionStatus::ACTIVE->value
            ]);

            if (empty($subscriptions)) {
                $io->success('No active subscriptions to update.');
                return Command::SUCCESS;
            }

            foreach ($subscriptions as $subscription) {
                if ($subscription->getNextBillingAt() < $now) {
                    $subscription->setStatus(SubscriptionStatus::EXPIRED->value);
                    $io->writeln(sprintf(
                        'Subscription ID %d expired (next billing: %s).',
                        $subscription->getId(),
                        $subscription->getNextBillingAt()->format('Y-m-d H:i:s')
                    ));

                    $activity = new ActivityLog();
                    $activity->setType(ActivityLogType::SUBSCRIPTION_EXPIRED->value);
                    $activity->setDescription(sprintf(
                        'Subscription #%d expired automatically',
                        $subscription->getId()
                    ));
                    $activity->setRelatedType(ActivityLogType::SUBSCRIPTION_EXPIRED->getRelatedType());
                    $activity->setRelatedId($subscription->getId());
                    $activity->setUser(null);
                    $this->em->persist($activity);

                    $this->logger->info(
                        'Subscription expired automatically',
                        [
                            'subscription_id' => $subscription->getId(),
                            'next_billing_at' => $subscription->getNextBillingAt()->format('Y-m-d H:i:s')
                        ]
                    );
                }
            }

            $this->em->flush();

            $io->success('Subscription status update completed successfully.');
            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to update subscription statuses',
                [
                    'exception' => $e->getMessage(),
                ]
            );

            $io->error('An error occurred while updating subscription statuses.');
            return Command::FAILURE;
        }
    }

}