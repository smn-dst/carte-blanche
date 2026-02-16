<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for resetting a user's password.
 */
final class ResetPasswordInputDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 4096)]
        public string $plainPassword = '',

        #[Assert\NotBlank]
        #[Assert\EqualTo(propertyPath: 'plainPassword', message: 'Passwords do not match.')]
        public string $confirmPassword = '',
    ) {
    }
}
