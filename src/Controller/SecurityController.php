<?php

namespace App\Controller;

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
     * @param AuthenticationUtils $authenticationUtils
     * @param LoggerInterface $logger
     * @return Response
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        try {
            $lastUsername = $authenticationUtils->getLastUsername();
            $error = $authenticationUtils->getLastAuthenticationError();

            if ($error) {
                $logger->info(
                    'Authentication failed on login page.',
                    [
                        'username' => $lastUsername,
                        'exception' => $error,
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );
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