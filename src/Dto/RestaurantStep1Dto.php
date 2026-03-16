<?php

namespace App\Dto;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

final class RestaurantStep1Dto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',

        public ?string $description = null,

        /** @var Category[] */
        public array $categories = [],
    ) {
    }
}
