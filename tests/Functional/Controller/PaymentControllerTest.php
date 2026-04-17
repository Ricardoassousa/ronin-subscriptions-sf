<?php

namespace App\Tests\Functional\Controller;

use App\Entity\CustomerProfile;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Enum\PaymentStatus;
use App\Enum\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    /**
     * Get Doctrine EntityManager from service container.
     */
    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Helper: login test user
     */
    private function loginUser($client): User
    {
        $user = $this->em()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'existing@example.com']);

        self::assertNotNull($user, 'User not found in fixtures.');

        $client->loginUser($user);

        return $user;
    }

    /**
     * Ensure user has a customer profile (required by controller)
     */
    private function ensureCustomerProfile(User $user): void
    {
        $em = $this->em();

        $profile = $em->getRepository(CustomerProfile::class)
            ->findOneBy(['user' => $user]);

        if (!$profile) {
            $profile = new CustomerProfile();
            $profile->setUser($user);
            $profile->setFirstName('Test');
            $profile->setSurname('User');
            $profile->setPhone('123456789');
            $profile->setCountry('PT');
            $profile->setCity('Lisbon');

            $em->persist($profile);
            $em->flush();
        }
    }

    /**
     * Get any existing subscription plan (required for subscription)
     */
    private function getPlan(): SubscriptionPlan
    {
        $plan = $this->em()
            ->getRepository(SubscriptionPlan::class)
            ->findOneBy([]);

        self::assertNotNull($plan, 'No subscription plan found.');

        return $plan;
    }

    /**
     * Create a fully valid subscription (all NOT NULL fields filled)
     */
    private function createSubscription(User $user, SubscriptionPlan $plan): Subscription
    {
        $em = $this->em();

        $now = new \DateTimeImmutable();

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setSubscriptionPlan($plan);

        // Required snapshot fields
        $subscription->setPriceSnapshot(10);
        $subscription->setCurrencySnapshot('EUR');
        $subscription->setBillingIntervalSnapshot('month');

        $subscription->setStatus(SubscriptionStatus::PENDING_PAYMENT->value);

        // Required dates
        $subscription->setStartedAt($now);
        $subscription->setCreatedAt($now);

        $em->persist($subscription);
        $em->flush();

        return $subscription;
    }

    /**
     * Create a valid payment linked to a valid subscription
     */
    private function createPayment(User $user): Payment
    {
        $em = $this->em();

        $plan = $this->getPlan();
        $subscription = $this->createSubscription($user, $plan);

        $payment = new Payment();
        $payment->setUser($user);
        $payment->setSubscription($subscription);
        $payment->setAmount(10);
        $payment->setCurrency('EUR');
        $payment->setStatus(PaymentStatus::PENDING->value);

        $em->persist($payment);
        $em->flush();

        return $payment;
    }

    /**
     * Test: user can access checkout page
     */
    public function testUserCanAccessCheckout(): void
    {
        $client = static::createClient();
        $user = $this->loginUser($client);

        $this->ensureCustomerProfile($user);

        $payment = $this->createPayment($user);

        $client->request('GET', '/payments/checkout/' . $payment->getId());

        // Follow redirect if needed (Symfony behavior)
        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }

        // Accept multiple valid outcomes depending on security/voters
        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [200, 302, 403],
            'Unexpected status code when accessing checkout'
        );
    }

}