<?php

namespace App\Form;

use App\Dto\RestaurantStep2Dto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RestaurantStep2FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'autocomplete' => 'street-address',
                    'data-address-autocomplete-target' => 'address',
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'html5' => true,
                'required' => false,
                'attr' => [
                    'data-address-autocomplete-target' => 'latitude',
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'html5' => true,
                'required' => false,
                'attr' => [
                    'data-address-autocomplete-target' => 'longitude',
                ],
            ])
            ->add('capacity', IntegerType::class, ['label' => 'Capacité']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantStep2Dto::class,
            'csrf_token_id' => 'restaurant_wizard_step2',
        ]);
    }
}
