<?php

namespace App\Tests\Security\Voter;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\User;
use App\Security\Voter\InvoiceVoter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class InvoiceVoterTest extends TestCase
{
    private InvoiceVoter $voter;
    private $securityMock;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->securityMock = $this->createMock(Security::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->voter = new InvoiceVoter($this->securityMock, $this->loggerMock);
    }

    public function testAdminUserIsAlwaysGranted(): void
    {
        $invoice = $this->createMock(Invoice::class);
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);

        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $vote = $this->voter->vote($token, $invoice, [InvoiceVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testOwnerIsGranted(): void
    {
        $user = new User();
        $payment = $this->createMock(Payment::class);
        $invoice = $this->createMock(Invoice::class);

        $invoice->method('getPayment')->willReturn($payment);
        $payment->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $vote = $this->voter->vote($token, $invoice, [InvoiceVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testNonOwnerIsDenied(): void
    {
        $owner = new User();
        $user = new User();
        $payment = $this->createMock(Payment::class);
        $invoice = $this->createMock(Invoice::class);

        $invoice->method('getPayment')->willReturn($payment);
        $payment->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->securityMock->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $this->loggerMock->expects($this->once())->method('log');

        $vote = $this->voter->vote($token, $invoice, [InvoiceVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

}