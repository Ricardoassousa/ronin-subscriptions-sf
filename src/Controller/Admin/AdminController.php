<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\ActivityLogType;
use App\Enum\PaymentStatus;
use App\Enum\SubscriptionStatus;
use App\Form\UserRolesType;
use App\Service\DashboardService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller used to manage administrative tasks.
 *
 * This controller handles admin-related actions such as:
 *  - Displaying the admin dashboard
 *  - Editing user roles
 *  - Managing other administrative functionalities
 */
class AdminController extends AbstractController
{
    /**
     * Displays the admin dashboard with the list of the latest registered users
     * and the most recent orders.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function indexUsers(EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        try {
            $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC'], 3);

            $logger->info(
                'Admin dashboard accessed',
                [
                    'latest_users' => count($users),
                    'admin_id' => $this->getUser() ? $this->getUser()->getId() : null,
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return $this->render('admin/index.html.twig', [
                'users' => $users
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Error loading admin dashboard',
                [
                    'exception' => $e->getMessage()
                ]
            );

            throw $e;
        }
    }

    /**
     * Displays a paginated list of users in the admin panel.
     *
     * This method retrieves User entities from the database, ordered by their
     * identifier in descending order (most recent users first), and paginates
     * the results using KnpPaginator.
     *
     * It is intended for administrative users to browse and manage registered
     * users of the e-commerce platform.
     *
     * Each user entry may display basic account information such as email,
     * roles, and optional customer profile details. Additional actions
     * (e.g. editing user roles) can be accessed from the list.
     *
     * The paginated user list is passed to the Twig template
     * 'admin/list_users.html.twig' for rendering.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function listUsers(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, LoggerInterface $logger): Response
    {
        try {
            $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);

            $pagination = $paginator->paginate(
                $users,
                $request->query->getInt('page', 1),
                10
            );

            $logger->info(
                'Admin viewed user list',
                [
                    'page' => $request->query->getInt('page', 1),
                    'total_users' => count($users),
                    'source' => [
                        'method' => __METHOD__,
                        'line' => __LINE__
                    ]
                ]
            );

            return $this->render('admin/list_users.html.twig', [
                'pagination' => $pagination
            ]);

        } catch (Throwable $e) {
            $logger->error(
                'Error listing users in admin',
                [
                    'exception' => $e->getMessage()
                ]
            );

            throw($e);
        }
    }

    /**
     * Allows editing the roles of a specific user.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param User $user
     * @param LoggerInterface $logger
     * @return Response
     */
    public function editUserRoles(Request $request, EntityManagerInterface $em, User $user, LoggerInterface $logger): Response
    {
        $form = $this->createForm(UserRolesType::class, $user);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setUpdatedAt(new DateTimeImmutable());
                $em->flush();

                $logger->warning(
                    'User roles updated by admin',
                    [
                        'target_user_id' => $user->getId(),
                        'new_roles' => $user->getRoles(),
                        'admin_id' => $this->getUser()->getId(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );

                $logger->notice(
                    'Admin edited user roles',
                    [
                        'user_id' => $user->getId(),
                        'source' => [
                            'method' => __METHOD__,
                            'line' => __LINE__
                        ]
                    ]
                );

                $this->addFlash('success', 'User roles updated successfully!');
                return $this->redirectToRoute('admin_users_list');
            }

        } catch (Throwable $e) {
            $logger->error(
                'Error editing user roles',
                [
                    'user_id' => $user->getId(),
                    'exception' => $e->getMessage()
                ]
            );

            throw($e);
        }

        return $this->render('admin/edit_user_roles.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * Displays the admin dashboard overview.
     *
     * Renders a summary page with key system metrics (KPIs) such as:
     * - Total subscriptions
     * - Active vs cancelled subscriptions
     * - Monthly recurring revenue (MRR)
     *
     * Additionally, provides a feed of the most recent activities (up to 20 by default).
     *
     * This method delegates the calculation of metrics and retrieval of activities
     * to the DashboardService, keeping the controller lean and focused on rendering.
     *
     * Logging is performed to track access by administrative users.
     *
     * @param DashboardService $dashboardService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function dashboard(DashboardService $dashboardService, LoggerInterface $logger): Response
    {
        $metrics = $dashboardService->getMetrics();
        $recentActivities = $dashboardService->getRecentActivities(20);

        $logger->info(
            'Admin dashboard accessed',
            [
                'admin_id' => $this->getUser() ? $this->getUser()->getId() : null,
                'source' => [
                    'method' => __METHOD__,
                    'line' => __LINE__
                ]
            ]
        );

        return $this->render('admin/dashboard.html.twig', [
            'metrics' => $metrics,
            'recentActivities' => $recentActivities
        ]);
    }

}