<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

/**
 * Controller responsible for user registration.
 *
 * Handles creating new users, processing the registration form,
 * hashing passwords, and persisting users to the database.
 */
class RegistrationController extends AbstractController
{
    /**
     * Handles user registration, including form submission, password hashing, and saving the new user to the database.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $passwordHasher
     * @param LoggerInterface $logger
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger): Response
    {
        try {
            $logger->info(
                'Registration page accessed.',
                [
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $email = $user->getEmail();

                if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                    $form->get('email')->addError(new FormError('This email is already registered.'));

                    $logger->info(
                        'Duplicate email attempted during registration.',
                        [
                            'email' => $email,
                            'source' => [
                                'method' => __METHOD__,
                                'line' => __LINE__
                            ]
                        ]
                    );
                }

                if ($form->isValid()) {
                    $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
                    $em->persist($user);

                    try {
                        $em->flush();
                    } catch (UniqueConstraintViolationException $e) {
                        $logger->error(
                            'Unique constraint violation on user registration.',
                            [
                                'email' => $email,
                                'exception' => $e
                            ]
                        );
                        $form->get('email')->addError(new FormError('This email is already registered.'));

                        return $this->render('registration/register.html.twig', [
                            'registrationForm' => $form->createView()
                        ]);
                    }

                    $logger->info(
                        'User registered successfully.',
                        [
                            'user_id' => $user->getId(),
                            'email' => $user->getEmail(),
                            'source' => [
                                'method' => __METHOD__,
                                'line' => __LINE__
                            ]
                        ]
                    );

                    $this->addFlash('success', 'Registration successful! You can now log in.');
                    return $this->redirectToRoute('app_login');
                }
            }

            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form->createView()
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Unexpected error during user registration.',
                [
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

}