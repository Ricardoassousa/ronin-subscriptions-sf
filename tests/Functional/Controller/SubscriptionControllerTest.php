<?php

namespace App\Tests\Functional\Controller;

use App\Entity\CustomerProfile;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubscriptionControllerTest extends WebTestCase
{
    /**
     * Get Doctrine EntityManager from service container.
     */
    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Log in a test user from fixtures.
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
     * Ensure the user has a valid customer profile.
     * Required because subscription flow depends on it.
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
     * Get any subscription plan from fixtures.
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
     * It should allow authenticated user to access subscription page.
     */
    public function testUserCanAccessSubscriptionIndex(): void
    {
        $client = static::createClient();
        $user = $this->loginUser($client);

        $this->ensureCustomerProfile($user);

        $client->request('GET', '/subscription/');

        $this->assertResponseIsSuccessful();
    }

    /**
     * It should allow user to start subscription flow.
     */
    public function testUserCanSubscribeToPlan(): void
    {
        $client = static::createClient();
        $user = $this->loginUser($client);

        $this->ensureCustomerProfile($user);

        $plan = $this->getPlan();

        $client->request('POST', '/subscription/subscribe/' . $plan->getId());

        // Subscription flow often redirects (payment or index)
        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [200, 302]
        );
    }

}