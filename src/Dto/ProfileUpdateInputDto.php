<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProfileUpdateInputDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Your first name should be at least {{ limit }} characters',
            maxMessage: 'Your first name should not be longer than {{ limit }} characters'
        )]
        public string $firstName = '',

        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Your last name should be at least {{ limit }} characters',
            maxMessage: 'Your last name should not be longer than {{ limit }} characters'
        )]
        public string $lastName = '',

        #[Assert\NotBlank]
        #[Assert\Regex(
            pattern: '/^\+?[0-9 ]{6,20}$/',
            message: 'Please enter a valid phone number.'
        )]
        public string $phoneNumber = '',
    )
    {
    }
}
