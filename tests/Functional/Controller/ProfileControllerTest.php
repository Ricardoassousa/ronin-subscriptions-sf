<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\ActivityLog;
use App\Enum\ActivityLogType;

class ProfileControllerTest extends WebTestCase
{
    /**
     * Helper: login test user
     */
    private function loginAsTestUser($client): User
    {
        $user = self::getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'existing@example.com']);

        $client->loginUser($user);

        return $user;
    }

    /**
     * It should allow authenticated user to access profile page
     */
    public function testProfilePageLoadsForAuthenticatedUser(): void
    {
        $client = static::createClient();

        $this->loginAsTestUser($client);

        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    /**
     * It should update user profile password and redirect
     */
    public function testUserCanUpdateProfilePassword(): void
    {
        $client = static::createClient();

        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/profile');

        $form = $crawler->selectButton('Save')->form([
            'user_profile_form[password][first]' => 'NewStrongPassword123!',
            'user_profile_form[password][second]' => 'NewStrongPassword123!',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/profile');
    }

    /**
     * It should hash password after update
     */
    public function testPasswordIsHashedAfterUpdate(): void
    {
        $client = static::createClient();

        $user = $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/profile');

        $plainPassword = 'NewStrongPassword123!';

        $form = $crawler->selectButton('Save')->form([
            'user_profile_form[password][first]' => $plainPassword,
            'user_profile_form[password][second]' => $plainPassword,
        ]);

        $client->submit($form);

        $updatedUser = self::getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
            ->find($user->getId());

        $this->assertNotSame($plainPassword, $updatedUser->getPassword());
        $this->assertNotEmpty($updatedUser->getPassword());
    }

    /**
     * It should create ActivityLog when password is changed
     */
    public function testActivityLogIsCreatedOnPasswordChange(): void
    {
        $client = static::createClient();

        $this->loginAsTestUser($client);

        $crawler = $client->request('GET', '/profile');

        $form = $crawler->selectButton('Save')->form([
            'user_profile_form[password][first]' => 'NewStrongPassword123!',
            'user_profile_form[password][second]' => 'NewStrongPassword123!',
        ]);

        $client->submit($form);

        $logs = self::getContainer()
            ->get('doctrine')
            ->getRepository(ActivityLog::class)
            ->findBy([
                'type' => ActivityLogType::PASSWORD_CHANGED->value
            ]);

        $this->assertNotEmpty($logs);
    }

    /**
     * It should block unauthenticated access
     */
    public function testGuestCannotAccessProfile(): void
    {
        $client = static::createClient();

        $client->request('GET', '/profile');

        $this->assertResponseRedirects();
    }

}