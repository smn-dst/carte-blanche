<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordInputDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Veuillez saisir votre mot de passe actuel.')]
        public string $currentPassword = '',

        #[Assert\NotBlank(message: 'Veuillez saisir un nouveau mot de passe.')]
        #[Assert\Length(
            min: 4,
            max: 4096,
            minMessage: 'Le nouveau mot de passe doit contenir au moins {{ limit }} caractères.',
        )]
        public string $newPassword = '',

        #[Assert\NotBlank(message: 'Veuillez confirmer votre nouveau mot de passe.')]
        #[Assert\EqualTo(
            propertyPath: 'newPassword',
            message: 'Les mots de passe ne correspondent pas.',
        )]
        public string $confirmPassword = '',
    ) {
    }
}
