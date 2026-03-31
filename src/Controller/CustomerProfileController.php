<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Cart;
use App\Entity\CustomerProfile;
use App\Enum\ActivityLogType;
use App\Form\CustomerProfileType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/**
 * Controller responsible for managing the customer's profile.
 *
 * This controller allows authenticated users to:
 *  - View and edit their customer profile
 *  - Create a profile if one does not yet exist
 *
 * The controller also integrates the user's active shopping cart
 * so that cart-related information can be displayed alongside the profile.
 *
 * Access is restricted to authenticated users.
 */
class CustomerProfileController extends AbstractController
{
    /**
     * Displays and processes the customer profile edit form.
     *
     * This method allows an authenticated user to view and update their customer profile.
     * If the user does not yet have a profile, a new one is created and associated
     * with the current user.
     *
     * The method also retrieves the user's active shopping cart (if any) and passes
     * it to the view so cart-related information can be displayed.
     *
     * If the form is submitted and valid, the profile is persisted and the user
     * is redirected back to the profile page with a success message.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     * @throws AccessDeniedException
     */
    public function edit(Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                throw new AccessDeniedException('User must be logged in.');
            }

            $logger->info(
                'Customer profile page accessed.',
                [
                    'user_id' => $user->getId(),
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $profile = $user->getCustomerProfile() ?? new CustomerProfile();
            $profile->setUser($user);

            $form = $this->createForm(CustomerProfileType::class, $profile);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $logger->info(
                    'Customer profile form submitted.',
                    [
                        'user_id' => $user->getId(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
            }

            if ($form->isSubmitted() && $form->isValid()) {
                $isNewProfile = $profile->getId() === null;

                $profile->setUpdatedAt(new DateTimeImmutable());
                $em->persist($profile);
                $em->flush();

                $logger->notice(
                    $isNewProfile ? 'New customer profile created.' : 'Customer profile updated.',
                    [
                        'user_id' => $user->getId(),
                        'profile_id' => $profile->getId(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );

                $this->addFlash('success', 'Profile saved successfully!');
                return $this->redirectToRoute('app_customer_profile');
            }

            return $this->render('customer_profile/edit.html.twig', [
                'form' => $form->createView()
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Unexpected error during customer profile edit.',
                [
                    'user_id' => $this->getUser() ? $this->getUser()->getId() : null,
                    'exception' => $e,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            throw $e;
        }
    }

}