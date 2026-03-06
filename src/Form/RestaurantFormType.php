<?php

namespace App\Form;

use App\Dto\RestaurantInputDto;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RestaurantFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])
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
            ->add('capacity', IntegerType::class, ['label' => 'Capacité'])
            ->add('askingPrice', TextType::class, ['label' => 'Prix demandé (€)'])
            ->add('annualRevenue', TextType::class, ['label' => "Chiffre d'affaires annuel (€)", 'required' => false])
            ->add('rent', TextType::class, ['label' => 'Loyer mensuel (€)', 'required' => false])
            ->add('leaseRemaining', IntegerType::class, ['label' => 'Bail restant (mois)', 'required' => false])
            ->add('pappersUrl', TextType::class, ['label' => 'URL Pappers', 'required' => false])
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
            ->add('ticketPrice', TextType::class, ['label' => 'Prix du ticket (€)', 'required' => false])
            ->add('maxCapacity', IntegerType::class, ['label' => 'Capacité max (tickets)'])
            ->add('uploadedImages', FileType::class, [
                'label' => 'Ajouter des images',
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            ])
            ->add('categories', EntityType::class, [
                'label' => 'Catégories',
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantInputDto::class,
            'csrf_token_id' => 'restaurant_form',
        ]);
    }
}
