<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Enum\ActivityLogType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Throwable;

/**
 * Controller responsible for user authentication.
 *
 * Handles displaying the login form, processing authentication errors,
 * and logging out users via the Symfony security firewall.
 */
class SecurityController extends AbstractController
{
    /**
     * Displays the login form and handles authentication errors.
     *
     * This method is called when the user visits the login page.
     * It displays the form with the last submitted username and any authentication error.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param AuthenticationUtils $authenticationUtils
     * @param LoggerInterface $logger
     * @return Response
     */
    public function login(Request $request, EntityManagerInterface $em, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        try {
            $lastUsername = $authenticationUtils->getLastUsername();
            $error = $authenticationUtils->getLastAuthenticationError();

            if ($error) {
                $activity = new ActivityLog();
                $activity->setType(ActivityLogType::LOGIN_FAILED->value);
                $activity->setDescription(sprintf(
                    'Failed login attempt for username "%s" from IP %s, user agent: %s',
                    $lastUsername,
                    $request->getClientIp(),
                    $request->headers->get('User-Agent')
                ));
                $activity->setRelatedType(ActivityLogType::LOGIN_FAILED->getRelatedType());
                // Optionally: set relatedId if username exists in DB
                $user = $em->getRepository(User::class)->findOneBy(['email' => $lastUsername]);
                if ($user !== null) {
                    $activity->setRelatedId($user->getId());
                    $activity->setUser($user);
                }

                $em->persist($activity);
                $em->flush();
            } else {
                $logger->info(
                    'Login page accessed.',
                    [
                        'ip' => $request->getClientIp(),
                        'user_agent' => $request->headers->get('User-Agent'),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
            }

            return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error
            ]);

        } catch (Throwable $e) {
            $logger->info(
                'Unexpected error while rendering login page.',
                [
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

    /**
     * Handles user logout.
     *
     * This method is intercepted by the Symfony firewall and does not need to do any processing.
     * The firewall will automatically log out the user and redirect them to the configured route.
     *
     * @param LoggerInterface $logger
     * @throws LogicException
     */
    public function logout(LoggerInterface $logger): void
    {
        try {
            $logger->info(
                'Logout action triggered.',
                [
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            throw new LogicException('Intercepted by the logout firewall.');
        } catch (LogicException $e) {
            $logger->info(
                'Logout intercepted by firewall.',
                [
                    'message' => $e->getMessage()
                ]
            );

            throw $e;
        } catch (Throwable $e) {
            $logger->info(
                'Unexpected error during logout.',
                [
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

}