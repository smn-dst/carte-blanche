<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cuisineTypes', ChoiceType::class, [
                'choices' => [
                    'Français' => 'french',
                    'Italien' => 'italian',
                    'Japonais' => 'japanese',
                    'Asiatique' => 'asian',
                    'Méditerranéen' => 'mediterranean',
                    'Burger / Fast Food' => 'fastfood',
                    'Brasserie' => 'brasserie',
                    'Gastronomique' => 'gastronomic',
                    'Végétarien / Vegan' => 'vegan',
                    'Fruits de mer' => 'seafood',
                    'Pizzeria' => 'pizza',
                    'Café / Brunch' => 'cafe',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'label' => false,
            ])

            ->add('budgetMin', NumberType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['min' => 0, 'max' => 2000000, 'step' => 10000],
            ])
            ->add('budgetMax', NumberType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['min' => 0, 'max' => 2000000, 'step' => 10000],
            ])

            ->add('preferredCity', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['placeholder' => 'Ex : Paris, Lyon, Bordeaux...', 'autocomplete' => 'off'],
            ])
            ->add('searchRadius', ChoiceType::class, [
                'choices' => [
                    '10 km' => 10,
                    '25 km' => 25,
                    '50 km' => 50,
                    '100 km' => 100,
                    'Toute la France' => 0,
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Rayon de recherche',
            ])

            ->add('capacityMin', ChoiceType::class, [
                'choices' => [
                    'Moins de 20 couverts' => 20,
                    '20 à 50 couverts' => 50,
                    '50 à 100 couverts' => 100,
                    'Plus de 100 couverts' => 101,
                    'Peu importe' => 0,
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Capacité minimale',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
        ]);
    }
}
