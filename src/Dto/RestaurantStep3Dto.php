<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RestaurantStep3Dto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le prix demandé est obligatoire.')]
        #[Assert\Positive]
        public ?string $askingPrice = null,

        #[Assert\PositiveOrZero]
        public ?string $annualRevenue = null,

        #[Assert\PositiveOrZero]
        public ?string $rent = null,

        #[Assert\PositiveOrZero]
        public ?int $leaseRemaining = null,

        #[Assert\Url(message: "L'URL Pappers est invalide.")]
        public ?string $pappersUrl = null,
    ) {
    }
}
