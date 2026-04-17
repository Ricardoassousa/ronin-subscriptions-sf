<?php

namespace App\Tests\Security\Voter;

use App\Entity\Payment;
use App\Entity\User;
use App\Enum\PaymentStatus;
use App\Security\Voter\PaymentVoter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PaymentVoterTest extends TestCase
{
    private PaymentVoter $voter;
    private Security $securityMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->securityMock = $this->createMock(Security::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->voter = new PaymentVoter($this->securityMock, $this->loggerMock);
    }

    public function testAdminCanAlwaysViewAndProcess(): void
    {
        $payment = $this->createMock(Payment::class);
        $user = $this->createMock(User::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $payment, [PaymentVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $payment, [PaymentVoter::PROCESS]));
    }

    public function testOwnerCanViewAndProcessValidPayment(): void
    {
        $user = new User();
        $payment = $this->createMock(Payment::class);
        $payment->method('getUser')->willReturn($user);
        $payment->method('getStatus')->willReturn(PaymentStatus::PENDING->value);
        $payment->method('getAmount')->willReturn(100.0); // float return type

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $payment, [PaymentVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $payment, [PaymentVoter::PROCESS]));
    }

    public function testOwnerCannotProcessSuccessPayment(): void
    {
        $user = new User();
        $payment = $this->createMock(Payment::class);
        $payment->method('getUser')->willReturn($user);
        $payment->method('getStatus')->willReturn(PaymentStatus::SUCCESS->value);
        $payment->method('getAmount')->willReturn(100.0); // float return type

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $payment, [PaymentVoter::PROCESS]));
    }

    public function testNonOwnerIsDenied(): void
    {
        $owner = new User();
        $user = new User();
        $payment = $this->createMock(Payment::class);
        $payment->method('getUser')->willReturn($owner);
        $payment->method('getStatus')->willReturn(PaymentStatus::PENDING->value);
        $payment->method('getAmount')->willReturn(100.0);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        // Allow logger to be called multiple times safely
        $this->loggerMock
            ->expects($this->any())
            ->method('log');

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $payment, [PaymentVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $payment, [PaymentVoter::PROCESS]));
    }

}