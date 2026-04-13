<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordInputDto
{
    #[Assert\NotBlank(message: 'Le mot de passe actuel est requis.')]
    public string $currentPassword = '';

    #[Assert\NotBlank(message: 'Le nouveau mot de passe est requis.')]
    #[Assert\Length(min: 10, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    public string $newPassword = '';

    #[Assert\NotBlank(message: 'La confirmation est requise.')]
    #[Assert\EqualTo(propertyPath: 'newPassword', message: 'Les mots de passe ne correspondent pas.')]
    public string $confirmPassword = '';
}
