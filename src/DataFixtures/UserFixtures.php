<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Existing user for duplicate email tests
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password123')
        );

        $manager->persist($user);
        $manager->flush();
    }

}