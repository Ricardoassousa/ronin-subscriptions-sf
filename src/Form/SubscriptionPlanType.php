<?php

namespace App\Form;

use App\Entity\SubscriptionPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Plan Name'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2
            ])
            ->add('discountPercent', NumberType::class, [
                'label' => 'Discount (%)',
                'scale' => 2,
                'required' => false
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Currency',
                'choices' => [
                    'Euro' => 'EUR',
                    'USD' => 'USD',
                    'GBP' => 'GBP'
                ],
                'required' => false
            ])
            ->add('billingInterval', ChoiceType::class, [
                'label' => 'Billing Interval',
                'choices' => [
                    'Monthly' => 'month',
                    'Yearly' => 'year'
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false
            ])
            ->add('trialDays', IntegerType::class, [
                'label' => 'Trial Days',
                'required' => false
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Featured',
                'required' => false
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Sort Order',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubscriptionPlan::class,
        ]);
    }
}