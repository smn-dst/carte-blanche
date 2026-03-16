<?php

namespace App\Form;

use App\Entity\UserPreferenceEmbedding;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserPreferenceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('preferencesText', TextareaType::class, [
            'label' => 'Mes préférences',
            'required' => false,
            'attr' => [
                'placeholder' => 'Décrivez vos préférences de restaurants (type de cuisine, budget, localisation…)',
                'rows' => 4,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreferenceEmbedding::class,
            'csrf_token_id' => 'profile_preferences',
        ]);
    }
}
