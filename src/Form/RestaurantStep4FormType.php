<?php

namespace App\Form;

use App\Dto\RestaurantStep4Dto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RestaurantStep4FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auctionDate', DateType::class, ['label' => "Date d'enchère", 'required' => false, 'widget' => 'single_text'])
            ->add('auctionTime', TimeType::class, ['label' => "Heure d'enchère", 'required' => false, 'widget' => 'single_text'])
            ->add('auctionLocation', TextType::class, [
                'label' => "Lieu d'enchère",
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-auction-location-autocomplete-target' => 'location',
                ],
            ])
            ->add('auctionLocationLat', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'data-auction-location-autocomplete-target' => 'latitude',
                ],
            ])
            ->add('auctionLocationLng', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'data-auction-location-autocomplete-target' => 'longitude',
                ],
            ])
            ->add('maxCapacity', IntegerType::class, ['label' => 'Capacité max (tickets)'])
            ->add('uploadedImages', FileType::class, [
                'label' => 'Ajouter des images',
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantStep4Dto::class,
            'csrf_token_id' => 'restaurant_wizard_step4',
        ]);
    }
}
