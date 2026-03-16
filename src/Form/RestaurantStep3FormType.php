<?php

namespace App\Form;

use App\Dto\RestaurantStep3Dto;
use App\Form\DataTransformer\MoneyTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RestaurantStep3FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('askingPrice', TextType::class, ['label' => 'Prix demandé (€)'])
            ->add('annualRevenue', TextType::class, ['label' => "Chiffre d'affaires annuel (€)", 'required' => false])
            ->add('rent', TextType::class, ['label' => 'Loyer mensuel (€)', 'required' => false]);

        $builder->get('askingPrice')->addModelTransformer(new MoneyTransformer());
        $builder->get('annualRevenue')->addModelTransformer(new MoneyTransformer());
        $builder->get('rent')->addModelTransformer(new MoneyTransformer());

        $builder
            ->add('leaseRemaining', IntegerType::class, ['label' => 'Bail restant (mois)', 'required' => false])
            ->add('pappersUrl', TextType::class, ['label' => 'URL Pappers', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantStep3Dto::class,
            'csrf_token_id' => 'restaurant_wizard_step3',
        ]);
    }
}
