<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\When;

class RegistrationControllerTest extends WebTestCase
{
    /**
     * It should display registration page
     */
    public function testRegistrationPageLoads(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    /**
     * It should register a new user successfully
     */
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'user_' . uniqid() . '@example.com',
            'registration_form[password][first]' => 'StrongPassword123!',
            'registration_form[password][second]' => 'StrongPassword123!',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    /**
     * It should prevent duplicate email registration (from fixtures)
     */
    public function testDuplicateEmailRegistration(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'existing@example.com',
            'registration_form[password][first]' => 'StrongPassword123!',
            'registration_form[password][second]' => 'StrongPassword123!',
        ]);

        $client->submit($form);

        // Should NOT redirect, should stay on form
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertSelectorTextContains(
            'form',
            'This email is already registered.'
        );
    }

    /**
     * It should validate invalid form data
     */
    public function testInvalidRegistrationData(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'not-an-email',
            'registration_form[password][first]' => '123',
            'registration_form[password][second]' => '123',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
    }

}