<?php

namespace App\Form;

use App\Dto\RegistrationInputDto;
use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
// use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// use Symfony\Component\Validator\Constraints\IsTrue;
// use Symfony\Component\Validator\Constraints\Length;
// use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('plainPassword')
            ->add('confirmPassword')
            ->add('firstName')
            ->add('lastName')
            ->add('phoneNumber')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Buyer' => 'ROLE_USER',
                    'Vendor' => 'ROLE_VENDOR',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('agreeTerms', CheckboxType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationInputDto::class,
            //            'empty_data' => fn($form) => new RegistrationInputDto(
            //                email: $form->get('email')->getData() ?? '',
            //                plainPassword: $form->get('plainPassword')->getData() ?? '',
            //                firstName: $form->get('firstName')->getData() ?? '',
            //                lastName: $form->get('lastName')->getData() ?? '',
            //                phoneNumber: $form->get('phoneNumber')->getData() ?? '',
            //                agreeTerms: $form->get('agreeTerms')->getData() ?? false,
            //                roles: $form->get('roles')->getData() ?? []
            //            ),
        ]);
    }
}
