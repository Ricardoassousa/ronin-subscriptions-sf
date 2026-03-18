<?php

namespace App\Controller\Admin;

use App\Entity\SubscriptionPlan;
use App\Form\SubscriptionPlanType;
use App\Service\SlugGenerator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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
     * Displays a list of all subscription plans.
     *
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $subscriptionPlans = $em->getRepository(SubscriptionPlan::class)->findBy([], ['sortOrder' => 'ASC']);

        $logger->info('Admin accessed subscription plan list.', [
            'plans_count' => count($subscriptionPlans),
            'controller' => __CLASS__,
            'method' => __METHOD__
        ]);

        return $this->render('admin/subscription_plan/index.html.twig', [
            'subscriptionPlans' => $subscriptionPlans
        ]);
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
                $subscriptionPlan->setFeatures(['Access to premium dashboard', 'Unlimited projects', 'Priority support']);
                $em->persist($subscriptionPlan);
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
     * Disables (soft deletes) a subscription plan.
     *
     * @param int $id
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     * @return Response
     */
    public function disable(int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->find($id);
        if (!$subscriptionPlan) {
            $logger->warning('Subscription plan not found for disable.', [
                'subscription_plan_id' => $id,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
            throw $this->createNotFoundException('Subscription plan not found');
        }

        $subscriptionPlan->setIsActive(false);
        $subscriptionPlan->setUpdatedAt(new DateTimeImmutable());

        try {
            $em->flush();

            $logger->notice('Subscription plan disabled successfully.', [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);

            $this->addFlash('success', 'Subscription plan disabled successfully!');
        } catch (Throwable $e) {
            $logger->error('Failed to disable subscription plan.', [
                'subscription_plan_id' => $subscriptionPlan->getId(),
                'exception' => $e,
                'controller' => __CLASS__,
                'method' => __METHOD__
            ]);
            $this->addFlash('error', 'Failed to disable subscription plan.');
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