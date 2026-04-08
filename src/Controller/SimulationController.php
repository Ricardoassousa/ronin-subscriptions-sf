<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\PaymentFailedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Controller used to simulate payment-related scenarios.
 *
 * Useful for testing event-driven flows without external providers.
 */
class SimulationController
{
    /**
     * Simulate a payment failure for a given user email.
     *
     * Checks if the user exists in the database. If the user exists,
     * dispatches a PaymentFailedEvent and logs the simulation. If the
     * user does not exist, logs a warning and returns HTTP 404.
     *
     * @param Request $request
     * @param EventDispatcherInterface $dispatcher
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function simulatePaymentFailed(Request $request, EventDispatcherInterface $dispatcher, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $email = $request->query->get('email');

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null) {
            $logger->warning(
                'Simulation aborted: user not found.',
                [
                    'email' => $email,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return new Response(
                "No user found with email: $email. Event not dispatched.",
                Response::HTTP_NOT_FOUND
            );
        }

        $logger->info(
            'Simulating payment failure.',
            [
                'email' => $email,
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        $dispatcher->dispatch(new PaymentFailedEvent($email, 'Card expired (simulation)'));

        return new Response(
            "Payment failure simulated for $email",
            Response::HTTP_OK
        );
    }

}