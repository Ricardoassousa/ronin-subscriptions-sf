<?php

namespace App\Tests\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Service\EmailNotificationService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailNotificationServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private EmailNotificationService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new EmailNotificationService($this->mailer, $this->logger);
    }

    public function testSendSubscriptionConfirmationLogsWarningWhenUserEmailMissing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn(null);

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(101);

        $this->logger->expects($this->once())
            ->method('warning');

        $this->mailer->expects($this->never())->method('send');

        $this->service->sendSubscriptionConfirmation($subscription);
    }

    public function testSendSubscriptionConfirmationSendsEmailWhenUserEmailPresent(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(202);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(TemplatedEmail::class));

        $this->logger->expects($this->never())->method('warning');

        $this->service->sendSubscriptionConfirmation($subscription);
    }

    public function testSendSubscriptionConfirmationCatchesMailerException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(303);

        $this->mailer->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->logger->expects($this->once())
            ->method('error');

        $this->service->sendSubscriptionConfirmation($subscription);
    }

    public function testSendRenewalReminderLogsWarningWhenUserEmailMissing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn(null);

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(401);

        $this->logger->expects($this->once())
            ->method('warning');

        $this->mailer->expects($this->never())->method('send');

        $this->service->sendRenewalReminder($subscription);
    }

    public function testSendRenewalReminderSendsEmailWhenUserEmailPresent(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(402);
        $subscription->method('getNextBillingAt')->willReturn(new DateTimeImmutable());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(TemplatedEmail::class));

        $this->logger->expects($this->never())->method('warning');

        $this->service->sendRenewalReminder($subscription);
    }

    public function testSendRenewalReminderCatchesMailerException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(403);
        $subscription->method('getNextBillingAt')->willReturn(new DateTimeImmutable());

        $this->mailer->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->logger->expects($this->once())
            ->method('error');

        $this->service->sendRenewalReminder($subscription);
    }

    public function testSendPaymentFailureLogsWarningWhenUserEmailMissing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn(null);

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(501);

        $this->logger->expects($this->once())->method('warning');
        $this->mailer->expects($this->never())->method('send');

        $this->service->sendPaymentFailure($subscription);
    }

    public function testSendPaymentFailureSendsEmailWhenUserEmailPresent(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(502);
        $subscription->method('getNextBillingAt')->willReturn(new DateTimeImmutable());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(TemplatedEmail::class));

        $this->logger->expects($this->never())->method('warning');

        $this->service->sendPaymentFailure($subscription);
    }

    public function testSendPaymentFailureCatchesMailerException(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('customer@example.com');

        $subscription = $this->createMock(Subscription::class);
        $subscription->method('getUser')->willReturn($user);
        $subscription->method('getId')->willReturn(503);
        $subscription->method('getNextBillingAt')->willReturn(new DateTimeImmutable());

        $this->mailer->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->logger->expects($this->once())->method('error');

        $this->service->sendPaymentFailure($subscription);
    }

}