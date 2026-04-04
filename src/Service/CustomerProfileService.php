<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class CustomerProfileService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CustomerProfileService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Checks if the user has a customer profile.
     * 
     * This method checks whether the given user has an associated customer profile.
     * If the profile is missing, a warning is logged.
     *
     * @param User $user
     * @return bool
     */
    public function hasCustomerProfile(User $user): bool
    {
        $customerProfile = $user->getCustomerProfile();
        if (!$customerProfile) {
            $this->logger->warning(
                'User does not have a customer profile.',
                [
                    'user_id' => $user->getId()
                ]
            );
            return false;
        }

        return true;
    }

}