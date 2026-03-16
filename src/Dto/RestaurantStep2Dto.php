<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RestaurantStep2Dto
{
    public function __construct(
        #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
        #[Assert\Length(max: 255)]
        public string $address = '',

        #[Assert\NotBlank(message: 'La latitude est obligatoire.')]
        #[Assert\Range(min: -90, max: 90)]
        public ?float $latitude = null,

        #[Assert\NotBlank(message: 'La longitude est obligatoire.')]
        #[Assert\Range(min: -180, max: 180)]
        public ?float $longitude = null,

        #[Assert\NotBlank(message: 'La capacité est obligatoire.')]
        #[Assert\Positive]
        public int $capacity = 0,
    ) {
    }
}
