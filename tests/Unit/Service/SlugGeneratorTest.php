<?php

namespace App\Tests\Service;

use App\Entity\SubscriptionPlan;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class SlugGeneratorTest extends TestCase
{
    private SlugGenerator $slugGenerator;
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;
    private LoggerInterface $logger;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        // Mock the repository
        $this->repository = $this->createMock(EntityRepository::class);

        // Mock the EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager
            ->method('getRepository')
            ->willReturn($this->repository);

        // Mock the Slugger
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->slugger
            ->method('slug')
            ->willReturnCallback(function ($name) {
                // Must return an instance of AbstractUnicodeString (here UnicodeString)
                return new UnicodeString(strtolower(str_replace(' ', '-', $name)));
            });

        // Mock the Logger
        $this->logger = $this->createMock(LoggerInterface::class);

        // Instantiate the SlugGenerator
        $this->slugGenerator = new SlugGenerator(
            $this->entityManager,
            $this->slugger,
            $this->logger
        );
    }

    public function testGenerateSlugSuccessfully(): void
    {
        $this->repository
            ->method('findOneBy')
            ->willReturn(null); // No conflicts

        $slug = $this->slugGenerator->generate('Premium Plan', SubscriptionPlan::class);

        $this->assertEquals('premium-plan', $slug);
    }

    public function testGenerateSlugWithConflicts(): void
    {
        // Simulate slug conflicts to test numeric suffix logic
        $this->repository
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                new SubscriptionPlan(), // conflict with base slug
                new SubscriptionPlan(), // conflict with first suffix
                null                     // available at second suffix
            );

        $slug = $this->slugGenerator->generate('Pro Plan', SubscriptionPlan::class);

        $this->assertEquals('pro-plan-2', $slug);
    }

    public function testGenerateSlugEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscription plan name cannot be empty.');

        $this->slugGenerator->generate('   ', SubscriptionPlan::class);
    }

    public function testGenerateSlugExceedsMaxAttemptsThrowsException(): void
    {
        // Always return conflict to trigger LogicException
        $this->repository
            ->method('findOneBy')
            ->willReturn(new SubscriptionPlan());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot generate a unique slug for this subscription plan name.');

        $this->slugGenerator->generate('Enterprise Plan', SubscriptionPlan::class);
    }

}