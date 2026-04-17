<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'admin-user';

    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_ADMIN']);

        $admin->setPassword(
            $this->hasher->hashPassword($admin, 'password123')
        );

        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::ADMIN_REFERENCE, $admin);
    }

}