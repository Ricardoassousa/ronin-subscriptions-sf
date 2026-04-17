<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    /**
     * Tests if the homepage returns a successful response (HTTP status 200).
     */
    public function testHomepageStatus()
    {
        $client = static::createClient();

        // Send a GET request to the homepage route
        $client->request('GET', '/');

        // Assert that the response is successful (HTTP status 200)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Tests if the homepage contains specific content (e.g., a welcome message).
     * This ensures that the page renders the expected content.
     */
    public function testHomepageContent()
    {
        $client = static::createClient();

        // Send a GET request to the homepage route
        $client->request('GET', '/');

        // Assert that the homepage contains a specific text (e.g., welcome message)
        $this->assertSelectorTextContains('h1', 'Welcome to Ronin Subscriptions');
    }

}