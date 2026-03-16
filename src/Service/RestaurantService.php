<?php

namespace App\Service;

use App\Dto\RestaurantInputDto;
use App\Dto\RestaurantStep1Dto;
use App\Dto\RestaurantStep2Dto;
use App\Dto\RestaurantStep3Dto;
use App\Dto\RestaurantStep4Dto;
use App\Entity\Image;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Exception\RestaurantNotFoundException;
use App\Repository\ImageRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class RestaurantService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RestaurantRepository $restaurantRepository,
        private ImageRepository $imageRepository,
    ) {
    }

    public function findOrFail(int $id): Restaurant
    {
        $restaurant = $this->restaurantRepository->find($id);
        if (null === $restaurant) {
            throw new RestaurantNotFoundException($id);
        }

        return $restaurant;
    }

    /**
     * @return Restaurant[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->restaurantRepository->findByOwner($owner);
    }

    public function buildInputDto(Restaurant $restaurant): RestaurantInputDto
    {
        return new RestaurantInputDto(
            name: $restaurant->getName() ?? '',
            description: $restaurant->getDescription(),
            address: $restaurant->getAddress() ?? '',
            latitude: $restaurant->getLatitude(),
            longitude: $restaurant->getLongitude(),
            capacity: $restaurant->getCapacity() ?? 0,
            askingPrice: $restaurant->getAskingPrice(),
            annualRevenue: $restaurant->getAnnualRevenue(),
            rent: $restaurant->getRent(),
            leaseRemaining: $restaurant->getLeaseRemaining(),
            pappersUrl: $restaurant->getPappersUrl(),
            auctionDate: $restaurant->getAuctionDate(),
            auctionTime: $restaurant->getAuctionTime(),
            auctionLocation: $restaurant->getAuctionLocation(),
            auctionLocationLat: $restaurant->getAuctionLocationLat(),
            auctionLocationLng: $restaurant->getAuctionLocationLng(),
            maxCapacity: $restaurant->getMaxCapacity(),
            categories: $restaurant->getCategories()->toArray(),
        );
    }

    public function buildInputDtoForStep(Restaurant $restaurant, int $step): object
    {
        return match ($step) {
            1 => new RestaurantStep1Dto(
                name: $restaurant->getName() ?? '',
                description: $restaurant->getDescription(),
                categories: $restaurant->getCategories()->toArray(),
            ),
            2 => new RestaurantStep2Dto(
                address: $restaurant->getAddress() ?? '',
                latitude: $restaurant->getLatitude(),
                longitude: $restaurant->getLongitude(),
                capacity: $restaurant->getCapacity() ?? 0,
            ),
            3 => new RestaurantStep3Dto(
                askingPrice: $restaurant->getAskingPrice(),
                annualRevenue: $restaurant->getAnnualRevenue(),
                rent: $restaurant->getRent(),
                leaseRemaining: $restaurant->getLeaseRemaining(),
                pappersUrl: $restaurant->getPappersUrl(),
            ),
            4 => new RestaurantStep4Dto(
                auctionDate: $restaurant->getAuctionDate(),
                auctionTime: $restaurant->getAuctionTime(),
                auctionLocation: $restaurant->getAuctionLocation(),
                auctionLocationLat: $restaurant->getAuctionLocationLat(),
                auctionLocationLng: $restaurant->getAuctionLocationLng(),
                maxCapacity: $restaurant->getMaxCapacity(),
            ),
            default => throw new \InvalidArgumentException("Step {$step} invalide."),
        };
    }

    public function create(User $owner, RestaurantInputDto $dto): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());
        $this->applyDto($restaurant, $dto);
        $this->entityManager->persist($restaurant);
        $this->saveImages($restaurant, $dto->uploadedImages);
        $this->entityManager->flush();

        return $restaurant;
    }

    public function update(Restaurant $restaurant, RestaurantInputDto $dto): void
    {
        $this->applyDto($restaurant, $dto);
        $restaurant->setUpdatedAt(new \DateTimeImmutable());
        $this->saveImages($restaurant, $dto->uploadedImages);
        $this->entityManager->flush();
    }

    public function updateStep(Restaurant $restaurant, object $stepDto): void
    {
        if ($stepDto instanceof RestaurantStep1Dto) {
            $restaurant->setName(trim($stepDto->name));
            $restaurant->setDescription($stepDto->description);
            foreach ($restaurant->getCategories() as $existing) {
                $restaurant->removeCategory($existing);
            }
            foreach ($stepDto->categories as $category) {
                $restaurant->addCategory($category);
            }
        } elseif ($stepDto instanceof RestaurantStep2Dto) {
            if (null === $stepDto->latitude || null === $stepDto->longitude) {
                throw new \InvalidArgumentException('Latitude et longitude sont obligatoires.');
            }
            $restaurant->setAddress(trim($stepDto->address));
            $restaurant->setLatitude($stepDto->latitude);
            $restaurant->setLongitude($stepDto->longitude);
            $restaurant->setCapacity($stepDto->capacity);
        } elseif ($stepDto instanceof RestaurantStep3Dto) {
            $restaurant->setAskingPrice($stepDto->askingPrice ?? '0');
            $restaurant->setAnnualRevenue($stepDto->annualRevenue);
            $restaurant->setRent($stepDto->rent);
            $restaurant->setLeaseRemaining($stepDto->leaseRemaining);
            $restaurant->setPappersUrl($stepDto->pappersUrl);
            $restaurant->setTicketPrice($this->calculateTicketPrice($stepDto->askingPrice ?? '0'));
        } elseif ($stepDto instanceof RestaurantStep4Dto) {
            $restaurant->setAuctionDate($stepDto->auctionDate);
            $restaurant->setAuctionTime($stepDto->auctionTime);
            $restaurant->setAuctionLocation($stepDto->auctionLocation);
            $restaurant->setAuctionLocationLat($stepDto->auctionLocationLat);
            $restaurant->setAuctionLocationLng($stepDto->auctionLocationLng);
            $restaurant->setMaxCapacity($stepDto->maxCapacity);
            $this->saveImages($restaurant, $stepDto->uploadedImages);
        } else {
            throw new \InvalidArgumentException('DTO type non supporté : '.get_class($stepDto));
        }

        $restaurant->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function delete(Restaurant $restaurant): void
    {
        foreach ($restaurant->getImages()->toArray() as $image) {
            $restaurant->removeImage($image);
            $this->entityManager->remove($image);
        }

        $this->entityManager->remove($restaurant);
        $this->entityManager->flush();
    }

    public function deleteImage(int $restaurantId, int $imageId): void
    {
        $image = $this->imageRepository->find($imageId);

        if (null === $image || $image->getRestaurant()?->getId() !== $restaurantId) {
            return;
        }

        $image->getRestaurant()->removeImage($image);
        $this->entityManager->remove($image);
        $this->entityManager->flush();
    }

    /**
     * @param array<int, UploadedFile> $uploadedFiles
     */
    private function saveImages(Restaurant $restaurant, array $uploadedFiles): void
    {
        $position = $restaurant->getImages()->count();

        foreach ($uploadedFiles as $uploadedFile) {
            $image = new Image();
            $image->setImageFile($uploadedFile);
            $image->setPosition($position);
            ++$position;
            $restaurant->addImage($image);
            $this->entityManager->persist($image);
        }
    }

    private function applyDto(Restaurant $restaurant, RestaurantInputDto $dto): void
    {
        if (null === $dto->latitude || null === $dto->longitude) {
            throw new \InvalidArgumentException('Latitude et longitude sont obligatoires.');
        }

        $restaurant->setName(trim($dto->name));
        $restaurant->setDescription($dto->description);
        $restaurant->setAddress(trim($dto->address));
        $restaurant->setLatitude($dto->latitude);
        $restaurant->setLongitude($dto->longitude);
        $restaurant->setCapacity($dto->capacity);
        $restaurant->setAskingPrice($dto->askingPrice ?? '0');
        $restaurant->setAnnualRevenue($dto->annualRevenue);
        $restaurant->setRent($dto->rent);
        $restaurant->setLeaseRemaining($dto->leaseRemaining);
        $restaurant->setPappersUrl($dto->pappersUrl);
        $restaurant->setAuctionDate($dto->auctionDate);
        $restaurant->setAuctionTime($dto->auctionTime);
        $restaurant->setAuctionLocation($dto->auctionLocation);
        $restaurant->setAuctionLocationLat($dto->auctionLocationLat);
        $restaurant->setAuctionLocationLng($dto->auctionLocationLng);
        $restaurant->setTicketPrice($this->calculateTicketPrice($dto->askingPrice ?? '0'));
        $restaurant->setMaxCapacity($dto->maxCapacity);

        foreach ($restaurant->getCategories() as $existing) {
            $restaurant->removeCategory($existing);
        }
        foreach ($dto->categories as $category) {
            $restaurant->addCategory($category);
        }
    }

    private function calculateTicketPrice(string $askingPrice): string
    {
        $price = (float) $askingPrice;

        return match (true) {
            $price < 100_000 => '50.00',
            $price <= 300_000 => '100.00',
            $price <= 500_000 => '200.00',
            default => '350.00',
        };
    }
}
