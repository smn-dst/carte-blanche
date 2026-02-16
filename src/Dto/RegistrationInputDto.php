<?php

namespace App\Dto;

use App\Validator\UniqueEmailConstraint;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RegistrationInputDto
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[UniqueEmailConstraint] // Constraint personnalisé pour vérifier l'unicité de l'email
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 6,
            max: 4096,
            minMessage: 'Your password should be at least {{ limit }} characters',
            maxMessage: 'Your password should not be longer than {{ limit }} characters'
        )]
        public string $plainPassword,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Your first name should be at least {{ limit }} characters',
            maxMessage: 'Your first name should not be longer than {{ limit }} characters'
        )]
        public string $firstName,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Your last name should be at least {{ limit }} characters',
            maxMessage: 'Your last name should not be longer than {{ limit }} characters'
        )]
        public string $lastName,
        #[Assert\NotBlank]
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{1,14}$/',
            message: 'Please enter a valid phone number.'
        )]
        public string $phoneNumber,
        #[Assert\IsTrue(message: 'You should agree to our terms.')]
        public bool $agreeTerms,
        #[Assert\NotBlank]
        public array $roles = [],
    ) {
    }
}
