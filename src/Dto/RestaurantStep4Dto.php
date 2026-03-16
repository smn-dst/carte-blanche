<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class RestaurantStep4Dto
{
    public function __construct(
        public ?\DateTimeInterface $auctionDate = null,

        public ?\DateTimeInterface $auctionTime = null,

        public ?string $auctionLocation = null,

        public ?float $auctionLocationLat = null,

        public ?float $auctionLocationLng = null,

        #[Assert\Positive]
        public int $maxCapacity = 50,

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
