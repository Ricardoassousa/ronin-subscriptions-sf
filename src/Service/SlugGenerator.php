<?php

namespace App\Service;

use App\Entity\SubscriptionPlan;
use App\Logger\AnalyticsLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service responsible for generating unique slugs for subscription plans.
 *
 * This service ensures that each subscription plan slug is unique in the database.
 * It also logs all operations for analytics purposes, including conflicts and final results.
 */
class SlugGenerator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SlugGenerator constructor.
     *
     * @param EntityManagerInterface $em
     * @param SluggerInterface $slugger
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
        $this->logger = $logger;
    }

    /**
     * Generates a unique slug for a given subscription plan.
     *
     * The method will attempt to generate a slug and, if a conflict is found in the database,
     * it will append a numeric suffix. If a unique slug cannot be generated after a maximum
     * number of attempts, a LogicException is thrown.
     *
     * @param string $name
     * @param string $entityClass
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function generate(string $name, string $entityClass): string
    {
        if (empty(trim($name))) {
            $this->logger->error(
                'Cannot generate slug for empty name',
                [
                    'base_name' => $name,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            throw new InvalidArgumentException('Subscription plan name cannot be empty.');
        }

        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        $slug = $baseSlug;
        $index = 1;
        $maxAttempts = 50;

        $this->logger->info(
            'Generating slug for subscription plan',
            [
                'base_name' => $name,
                'initial_slug' => $baseSlug,
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        while ($this->em->getRepository($entityClass)->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $index;
            $index++;

            $this->logger->debug(
                'Slug conflict found, trying new slug',
                [
                    'attempt_slug' => $slug,
                    'base_slug' => $baseSlug,
                    'index' => $index - 1,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            if ($index > $maxAttempts) {
                $this->logger->error(
                    'Failed to generate unique slug after maximum attempts',
                    [
                        'base_name' => $name,
                        'max_attempts' => $maxAttempts,
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );

                throw new LogicException('Cannot generate a unique slug for this subscription plan name.');
            }
        }

        $this->logger->info(
            'Slug successfully generated',
            [
                'final_slug' => $slug,
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        return $slug;
    }

}