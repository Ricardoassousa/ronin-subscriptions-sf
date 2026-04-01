<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\SubscriptionPlan;
use App\Entity\SubscriptionPlanSearch;
use App\Enum\ActivityLogType;
use App\Form\SubscriptionPlanType;
use App\Form\SubscriptionPlanSearchType;
use App\Service\SlugGenerator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controller responsible for managing subscription plans in the admin panel.
 *
 * Provides functionality for administrators to:
 *  - List all subscription plans
 *  - Create new plans
 *  - Edit existing plans
 *  - Disable plans (soft delete)
 *
 * All routes should be accessed by authenticated administrators.
 */
class SubscriptionPlanController extends AbstractController
{
    /**
     * Displays a list of all subscription plans with optional search/filter.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, LoggerInterface $logger): Response
    {
        try {
            $subscriptionPlanSearch = new SubscriptionPlanSearch();
            $searchForm = $this->createForm(SubscriptionPlanSearchType::class, $subscriptionPlanSearch);
            $searchForm->handleRequest($request);
            $searchParams = array();

            if ($searchForm->isSubmitted() && $searchForm->isValid()) {

                $search = $searchForm['search']->getData();
                $isActive = $searchForm['isActive']->getData();
                $billingInterval = $searchForm['billingInterval']->getData();
                $minPrice = $searchForm['minPrice']->getData();
                $maxPrice = $searchForm['maxPrice']->getData();
                $isFeatured = $searchForm['isFeatured']->getData();
                $minTrialDays = $searchForm['minTrialDays']->getData();
                $maxTrialDays = $searchForm['maxTrialDays']->getData();


                if (!empty($search)) {
                    $searchParams['search'] = $search;
                }
                if ($isActive !== null) {
                    $searchParams['isActive'] = $isActive;
                }
                if (!empty($billingInterval)) {
                    $searchParams['billingInterval'] = $billingInterval;
                }
                if ($minPrice !== null) {
                    $searchParams['minPrice'] = $minPrice;
                }
                if ($maxPrice !== null) {
                    $searchParams['maxPrice'] = $maxPrice;
                }
                if ($isFeatured !== null) {
                    $searchParams['isFeatured'] = $isFeatured;
                }
                if ($minTrialDays !== null) {
                    $searchParams['minTrialDays'] = $minTrialDays;
                }
                if ($maxTrialDays !== null) {
                    $searchParams['maxTrialDays'] = $maxTrialDays;
                }
            }

            $query = $em->getRepository(SubscriptionPlan::class)->findSubscriptionPlanByFilterQuery($searchParams);

            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10
            );

            $logger->info('Admin accessed subscription plan list.', [
                'plans_count' => count($pagination),
                'filters' => $searchParams,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);

            return $this->render('admin/subscription_plan/index.html.twig', [
                'pagination' => $pagination,
                'searchForm' => $searchForm->createView()
            ]);

        } catch (Throwable $e) {
            $logger->error('Error accessing subscription plan list.', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            dd($e);

            $this->addFlash('error', 'An error occurred while loading the subscription plans list.');
        }

    }

    /**
     * Creates a new subscription plan.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SlugGenerator $slugGenerator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $em, SlugGenerator $slugGenerator, LoggerInterface $logger): Response
    {
        $subscriptionPlan = new SubscriptionPlan();
        $form = $this->createForm(SubscriptionPlanType::class, $subscriptionPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $logger->info('Subscription plan creation form submitted.', [
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $slug = $slugGenerator->generate($subscriptionPlan->getName(), SubscriptionPlan::class);
                $subscriptionPlan->setSlug($slug);
                $em->persist($subscriptionPlan);
                $em->flush();

                $activity = new ActivityLog();
                $activity->setType(ActivityLogType::PLAN_CREATED->value);
                $activity->setDescription('New subscription plan created: ' . $subscriptionPlan->getName());
                $activity->setRelatedType(ActivityLogType::PLAN_CREATED->getRelatedType());
                $activity->setRelatedId($subscriptionPlan->getId());
                $activity->setUser($this->getUser());
                $em->persist($activity);
                $em->flush();

                $logger->notice('Subscription plan created successfully.', [
                    'subscription_plan_id' => $subscriptionPlan->getId(),
                    'name' => $subscriptionPlan->getName(),
                    'controller' => __CLASS__,
                    'method' => __METHOD__
                ]);

                $this->addFlash('success', 'Subscription plan created successfully!');
                return $this->redirectToRoute('subscription_plan_index');
            } catch (Throwable $e) {
                $logger->error('Failed to create subscription plan.', [
                    'exception' => $e,
                    'controller' => __CLASS__,
                    'method' => __METHOD__
                ]);

                $this->addFlash('error', 'Failed to create subscription plan.');
            }
        }

        return $this->render('admin/subscription_plan/new.html.twig', [
            'form' => $form->createView(),
            'subscriptionPlans' => $subscriptionPlan
        ]);
    }

    /**
     * Edits an existing subscription plan.
     *
     * @param int $id
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SlugGenerator $slugGenerator
     * @param LoggerInterface $logger
     * @return Response
     */
    public function edit(int $id, Request $request, EntityManagerInterface $em, SlugGenerator $slugGenerator, LoggerInterface $logger): Response
    {
        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($id);
        if (!$subscriptionPlan) {
            $logger->warning('Subscription plan not found for edit.', [
                'subscription_plan_id' => $id,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
            throw $this->createNotFoundException('Subscription plan not found');
        }

        $form = $this->createForm(SubscriptionPlanType::class, $subscriptionPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $logger->info('Subscription plan edit form submitted.', [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $logger->info('Updating subscription plan fields.', [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'name' => $subscriptionPlan->getName(),
                'price' => $subscriptionPlan->getPrice(),
                'currency' => $subscriptionPlan->getCurrency(),
                'billing_interval' => $subscriptionPlan->getBillingInterval(),
                'features' => $subscriptionPlan->getFeatures()
            ]);

            $oldSlug = $subscriptionPlan->getSlug();
            $newSlug = $slugGenerator->generate($subscriptionPlan->getName(), SubscriptionPlan::class);
            $subscriptionPlan->setUpdatedAt(new DateTimeImmutable());

            try {
                $em->flush();

                $activity = new ActivityLog();
                $activity->setType(ActivityLogType::PLAN_UPDATED->value);
                $activity->setDescription('Subscription plan updated: ' . $subscriptionPlan->getName());
                $activity->setRelatedType(ActivityLogType::PLAN_UPDATED->getRelatedType());
                $activity->setRelatedId($subscriptionPlan->getId());
                $activity->setUser($this->getUser());
                $em->persist($activity);
                $em->flush();

                $logger->notice('Subscription plan updated successfully.', [
                    'subscription_plan_id' => $subscriptionPlan->getId(),
                    'controller' => __CLASS__,
                    'method' => __METHOD__
                ]);

                $this->addFlash('success', 'Plan updated successfully!');
                return $this->redirectToRoute('subscription_plan_index');
            } catch (Throwable $e) {
                $logger->error('Failed to update subscription plan.', [
                    'subscription_plan_id' => $subscriptionPlan->getId(),
                    'exception' => $e,
                    'controller' => __CLASS__,
                    'method' => __METHOD__
                ]);
                $this->addFlash('error', 'Failed to update subscription plan.');
            }
        }

        return $this->render('admin/subscription_plan/edit.html.twig', [
            'form' => $form->createView(),
            'subscriptionPlans' => $subscriptionPlan
        ]);
    }

    /**
     * Toggle plan status: enable / disable (soft delete)
     *
     * @param int $id
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function toggle(int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($id);
        if (!$subscriptionPlan) {
            $logger->warning('Subscription plan not found.', [
                'subscription_plan_id' => $id,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
            throw $this->createNotFoundException('Subscription plan not found');
        }

        $subscriptionPlan->setIsActive(!$subscriptionPlan->isActive());
        $subscriptionPlan->setUpdatedAt(new DateTimeImmutable());

        try {
            $em->flush();

            $status = $subscriptionPlan->isActive() ? 'enabled' : 'disabled';

            $activity = new ActivityLog();
            $activity->setType(ActivityLogType::PLAN_TOGGLED->value);
            $activity->setDescription("Subscription plan {$status}: " . $subscriptionPlan->getName());
            $activity->setRelatedType(ActivityLogType::PLAN_TOGGLED->getRelatedType());
            $activity->setRelatedId($subscriptionPlan->getId());
            $activity->setUser($this->getUser());
            $em->persist($activity);
            $em->flush();

            $logger->notice("Subscription plan {$status}.", [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);

            $this->addFlash('success', "Subscription plan {$status} successfully!");
        } catch (Throwable $e) {
            $logger->error('Failed to toggle subscription plan.', [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'exception' => $e,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
            $this->addFlash('error', 'Failed to update subscription plan status.');
        }

        return $this->redirectToRoute('subscription_plan_index');
    }

    /**
     * Displays the details of a single subscription plan.
     *
     * @param int $id The ID of the subscription plan to display.
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function show(int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($id);

        if (!$subscriptionPlan) {
            $logger->warning('Subscription plan not found for show.', [
                'subscription_plan_id' => $id,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);

            throw $this->createNotFoundException('Subscription plan not found.');
        }

        $logger->info('Displaying subscription plan details.', [
            'subscription_plan_id' => $subscriptionPlan->getId(),
            'name' => $subscriptionPlan->getName(),
            'is_active' => $subscriptionPlan->isActive(),
            'price' => $subscriptionPlan->getPrice(),
            'billing_interval' => $subscriptionPlan->getBillingInterval(),
            'controller' => __CLASS__,
            'method' => __METHOD__
        ]);


        return $this->render('admin/subscription_plan/show.html.twig', [
            'subscriptionPlan' => $subscriptionPlan
        ]);
    }

}