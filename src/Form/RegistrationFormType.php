<?php

namespace App\Form;

use App\Dto\RegistrationInputDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'required' => true,
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Entrez votre mot de passe',
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'required' => true,
                'label' => 'Confirmez votre mot de passe',
                'attr' => [
                    'placeholder' => 'Entrez à nouveau votre mot de passe',
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'required' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'required' => true,
                'label_html' => true,
                'label' => 'J\'accepte les <span class="text-gradient"> conditions d\'utilisation </span> et la <span class="text-gradient"> politique de confidentialité </span>',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationInputDto::class,
        ]);
    }
}
