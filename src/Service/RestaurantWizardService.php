<?php

namespace App\Service;

use App\Dto\RestaurantInputDto;
use App\Dto\RestaurantStep1Dto;
use App\Dto\RestaurantStep2Dto;
use App\Dto\RestaurantStep3Dto;
use App\Dto\RestaurantStep4Dto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class RestaurantWizardService
{
    public const string SESSION_KEY = 'restaurant_wizard';

    public function __construct(
        private RequestStack $requestStack,
        private CategoryRepository $categoryRepository,
    ) {
    }

    public function saveStep(int $step, object $dto): void
    {
        $session = $this->requestStack->getSession();
        /** @var array<string, array<string, mixed>> $data */
        $data = $session->get(self::SESSION_KEY, []);
        $data["step{$step}"] = $this->serializeDto($dto);
        $session->set(self::SESSION_KEY, $data);
    }

    public function getStep1Dto(): RestaurantStep1Dto
    {
        $data = $this->getStepData(1);
        if (null === $data) {
            return new RestaurantStep1Dto();
        }

        /** @var int[] $categoryIds */
        $categoryIds = $data['categoryIds'] ?? [];
        $categories = array_values(array_filter(
            array_map(fn (int $id): ?Category => $this->categoryRepository->find($id), $categoryIds)
        ));

        return new RestaurantStep1Dto(
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            categories: $categories,
        );
    }

    public function getStep2Dto(): RestaurantStep2Dto
    {
        $data = $this->getStepData(2);
        if (null === $data) {
            return new RestaurantStep2Dto();
        }

        return new RestaurantStep2Dto(
            address: (string) ($data['address'] ?? ''),
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            capacity: (int) ($data['capacity'] ?? 0),
        );
    }

    public function getStep3Dto(): RestaurantStep3Dto
    {
        $data = $this->getStepData(3);
        if (null === $data) {
            return new RestaurantStep3Dto();
        }

        return new RestaurantStep3Dto(
            askingPrice: isset($data['askingPrice']) ? (string) $data['askingPrice'] : null,
            annualRevenue: isset($data['annualRevenue']) ? (string) $data['annualRevenue'] : null,
            rent: isset($data['rent']) ? (string) $data['rent'] : null,
            leaseRemaining: isset($data['leaseRemaining']) ? (int) $data['leaseRemaining'] : null,
            pappersUrl: isset($data['pappersUrl']) ? (string) $data['pappersUrl'] : null,
        );
    }

    public function getStep4Dto(): RestaurantStep4Dto
    {
        // UploadedFile values are never stored in session
        return new RestaurantStep4Dto();
    }

    public function buildFullDto(RestaurantStep4Dto $step4): RestaurantInputDto
    {
        $step1 = $this->getStep1Dto();
        $step2 = $this->getStep2Dto();
        $step3 = $this->getStep3Dto();

        return new RestaurantInputDto(
            name: $step1->name,
            description: $step1->description,
            address: $step2->address,
            latitude: $step2->latitude,
            longitude: $step2->longitude,
            capacity: $step2->capacity,
            askingPrice: $step3->askingPrice,
            annualRevenue: $step3->annualRevenue,
            rent: $step3->rent,
            leaseRemaining: $step3->leaseRemaining,
            pappersUrl: $step3->pappersUrl,
            auctionDate: $step4->auctionDate,
            auctionTime: $step4->auctionTime,
            auctionLocation: $step4->auctionLocation,
            auctionLocationLat: $step4->auctionLocationLat,
            auctionLocationLng: $step4->auctionLocationLng,
            maxCapacity: $step4->maxCapacity,
            categories: $step1->categories,
            uploadedImages: $step4->uploadedImages,
        );
    }

    public function clearWizardSession(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    public function isStepAccessible(int $step): bool
    {
        if (1 === $step) {
            $this->clearWizardSession();

            return true;
        }

        /** @var array<string, array<string, mixed>> $data */
        $data = $this->requestStack->getSession()->get(self::SESSION_KEY, []);

        for ($i = 1; $i < $step; ++$i) {
            if (!isset($data["step{$i}"])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getStepData(int $step): ?array
    {
        /** @var array<string, array<string, mixed>> $data */
        $data = $this->requestStack->getSession()->get(self::SESSION_KEY, []);

        return $data["step{$step}"] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDto(object $dto): array
    {
        if ($dto instanceof RestaurantStep1Dto) {
            return [
                'name' => $dto->name,
                'description' => $dto->description,
                'categoryIds' => array_map(fn (Category $c): ?int => $c->getId(), $dto->categories),
            ];
        }

        if ($dto instanceof RestaurantStep2Dto) {
            return [
                'address' => $dto->address,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'capacity' => $dto->capacity,
            ];
        }

        if ($dto instanceof RestaurantStep3Dto) {
            return [
                'askingPrice' => $dto->askingPrice,
                'annualRevenue' => $dto->annualRevenue,
                'rent' => $dto->rent,
                'leaseRemaining' => $dto->leaseRemaining,
                'pappersUrl' => $dto->pappersUrl,
            ];
        }

        throw new \InvalidArgumentException('DTO type non supporté pour la sérialisation : '.get_class($dto));
    }
}
