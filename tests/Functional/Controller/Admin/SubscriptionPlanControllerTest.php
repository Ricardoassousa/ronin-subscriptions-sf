<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\SubscriptionPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubscriptionPlanControllerTest extends WebTestCase
{
    /**
     * Get Doctrine EntityManager from service container.
     */
    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Log in an admin user using Symfony test client.
     *
     * Requires an admin user to exist in fixtures.
     */
    private function loginAdmin($client): void
    {
        $user = $this->em()
            ->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => 'admin@test.com']);

        self::assertNotNull($user, 'Admin user not found. Load fixtures first.');

        $client->loginUser($user);
    }

    /**
     * It should redirect to canonical URL and then load admin index page.
     */
    public function testIndexLoadsForAdmin(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $client->request('GET', '/admin/subscription-plan');

        $this->assertResponseRedirects('/admin/subscription-plan/');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table, form');
    }

    /**
     * It should allow admin to create a new subscription plan.
     */
    public function testAdminCanCreateSubscriptionPlan(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/admin/subscription-plan/new');

        $form = $crawler->selectButton('Create Plan')->form([
            'subscription_plan[name]' => 'Basic Plan',
            'subscription_plan[description]' => 'Test description',
            'subscription_plan[price]' => 9.99,
            'subscription_plan[currency]' => 'EUR',
            'subscription_plan[billingInterval]' => 'month',
            'subscription_plan[features]' => json_encode([
                'api_access' => true,
            ]),
            'subscription_plan[isActive]' => true,
        ]);

        $client->submit($form);
        $client->followRedirect();

        $plan = $this->em()
            ->getRepository(SubscriptionPlan::class)
            ->findOneBy(['name' => 'Basic Plan']);

        $this->assertNotNull($plan);
        $this->assertSame('Basic Plan', $plan->getName());
        $this->assertSame('EUR', $plan->getCurrency());
    }

}