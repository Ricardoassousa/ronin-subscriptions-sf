<?php

namespace App\Controller;

use App\Form\UserProfileFormType;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

/**
 * Controller responsible for managing the authenticated user's profile.
 *
 * Allows editing user information and updating passwords.
 */
class ProfileController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Edit the authenticated user's profile.
     *
     * @param Request $request
     * @param LoggerInterface $logger
     * @return Response
     */
    public function editProfile(Request $request, LoggerInterface $logger): Response
    {
        $user = $this->getUser();

        try {
            $logger->info(
                'Profile edit page accessed.',
                [
                    'user_id' => $user?->getId(),
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $form = $this->createForm(UserProfileFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $plainPassword = $form->get('password')->getData();
                if ($plainPassword) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
                    $user->setUpdatedAt(new DateTimeImmutable());

                    $logger->info(
                        'User password updated.',
                        [
                            'user_id' => $user->getId(),
                            'source' => [
                                'method' => __METHOD__,
                                'line' => __LINE__
                            ]
                        ]
                    );
                }

                $this->em->flush();

                $logger->info(
                    'User profile updated successfully.',
                    [
                        'user_id' => $user->getId(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                ]);

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('app_profile');
            }

            return $this->render('profile/edit.html.twig', [
                'profileForm' => $form->createView()
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Unexpected error during profile edit.',
                [
                    'user_id' => $user?->getId(),
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

}