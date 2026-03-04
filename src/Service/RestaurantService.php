<?php

namespace App\Service;

use App\Dto\RestaurantInputDto;
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
            ticketPrice: $restaurant->getTicketPrice(),
            maxCapacity: $restaurant->getMaxCapacity(),
            categories: $restaurant->getCategories()->toArray(),
        );
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

    public function delete(Restaurant $restaurant): void
    {
        foreach ($restaurant->getImages()->toArray() as $image) {
            if (!$image instanceof Image) {
                continue;
            }

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

    private function saveImages(Restaurant $restaurant, array $uploadedFiles): void
    {
        $position = $restaurant->getImages()->count();

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

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
        $restaurant->setTicketPrice($dto->ticketPrice);
        $restaurant->setMaxCapacity($dto->maxCapacity);

        foreach ($restaurant->getCategories() as $existing) {
            $restaurant->removeCategory($existing);
        }
        foreach ($dto->categories as $category) {
            $restaurant->addCategory($category);
        }
    }
}
