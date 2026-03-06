<?php

namespace App\Dto;

use App\Entity\Category;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class RestaurantInputDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $name = '',

        public ?string $description = null,

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

        public ?\DateTimeInterface $auctionDate = null,

        public ?\DateTimeInterface $auctionTime = null,

        public ?string $auctionLocation = null,

        public ?float $auctionLocationLat = null,

        public ?float $auctionLocationLng = null,

        #[Assert\PositiveOrZero]
        public ?string $ticketPrice = null,

        #[Assert\Positive]
        public int $maxCapacity = 50,

        /** @var Category[] */
        public array $categories = [],

        /**
         * @var UploadedFile[]
         */
        #[Assert\All([
            new Assert\File(
                maxSize: '5M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Seuls les formats JPEG, PNG et WebP sont acceptés.',
                maxSizeMessage: "L'image ne doit pas dépasser 5 Mo.",
            ),
        ])]
        public array $uploadedImages = [],
    ) {
    }
}
