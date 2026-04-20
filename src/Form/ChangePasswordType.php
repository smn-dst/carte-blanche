<?php

namespace App\Form;

use App\Dto\ChangePasswordInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirmer le nouveau mot de passe',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordInputDto::class,
        ]);
    }
}
