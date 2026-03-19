<?php

namespace App\Form;

use App\Entity\SubscriptionPlanSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SubscriptionPlanSearchType
 *
 * Symfony form used to filter subscription plans.
 * Uses GET method for query string filtering.
 */
class SubscriptionPlanSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => 'Search',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Name or slug'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Status',
                'required' => false,
                'placeholder' => 'All',
                'choices' => [
                    'Active' => true,
                    'Disabled' => false,
                ]
            ])
            ->add('billingInterval', ChoiceType::class, [
                'label' => 'Billing Interval',
                'required' => false,
                'placeholder' => 'All',
                'choices' => [
                    'Monthly' => 'month',
                    'Yearly' => 'year',
                ]
            ])
            ->add('minPrice', NumberType::class, [
                'label' => 'Min Price',
                'required' => false
            ])
            ->add('maxPrice', NumberType::class, [
                'label' => 'Max Price',
                'required' => false
            ])
            ->add('isFeatured', ChoiceType::class, [
                'label' => 'Featured',
                'required' => false,
                'placeholder' => 'All',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ]
            ])
            ->add('minTrialDays', IntegerType::class, [
                'label' => 'Min Trial Days',
                'required' => false
            ])
            ->add('maxTrialDays', IntegerType::class, [
                'label' => 'Max Trial Days',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubscriptionPlanSearch::class
        ]);
    }

}