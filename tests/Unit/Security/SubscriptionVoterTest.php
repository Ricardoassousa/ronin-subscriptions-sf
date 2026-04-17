<?php

namespace App\Tests\Security\Voter;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\SubscriptionStatus;
use App\Security\Voter\SubscriptionVoter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SubscriptionVoterTest extends TestCase
{
    private SubscriptionVoter $voter;
    private Security $securityMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->securityMock = $this->createMock(Security::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->voter = new SubscriptionVoter($this->securityMock, $this->loggerMock);
    }

    public function testAdminCanAccessAll(): void
    {
        $subscription = $this->createMock(Subscription::class);
        $user = new User();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subscription, [SubscriptionVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subscription, [SubscriptionVoter::CANCEL]));
    }

    public function testOwnerAccessBasedOnStatus(): void
    {
        $user = new User();
        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        // Owner should be able to VIEW and CANCEL an ACTIVE subscription
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subscription, [SubscriptionVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subscription, [SubscriptionVoter::CANCEL]));

        // PAUSE is allowed only for ACTIVE subscriptions
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subscription, [SubscriptionVoter::PAUSE]));
    }

    public function testOwnerCannotResumeIfNotPaused(): void
    {
        $user = new User();
        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getStatus')->willReturn(SubscriptionStatus::ACTIVE->value);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        // Cannot RESUME because subscription is ACTIVE, not PAUSED
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subscription, [SubscriptionVoter::RESUME]));
    }

    public function testNonOwnerIsDenied(): void
    {
        $owner = new User();
        $user = new User();

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        // Logger can be called any number of times
        $this->loggerMock->expects($this->any())->method('log');

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subscription, [SubscriptionVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subscription, [SubscriptionVoter::CANCEL]));
    }

}