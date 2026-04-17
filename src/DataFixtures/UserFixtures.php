<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\CustomerProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_NO_PROFILE = 'user-no-profile';
    public const USER_WITH_PROFILE = 'user-with-profile';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // User WITHOUT profile
        $user1 = new User();
        $user1->setEmail('existing@example.com');
        $user1->setPassword(
            $this->passwordHasher->hashPassword($user1, 'password123')
        );

        $manager->persist($user1);
        $this->addReference(self::USER_NO_PROFILE, $user1);

        // User WITH profile
        $user2 = new User();
        $user2->setEmail('withprofile@example.com');
        $user2->setPassword(
            $this->passwordHasher->hashPassword($user2, 'password123')
        );

        $profile = new CustomerProfile();
        $profile->setUser($user2);
        $profile->setFirstName('John');

        $manager->persist($user2);
        $manager->persist($profile);

        $this->addReference(self::USER_WITH_PROFILE, $user2);

        $manager->flush();
    }

}