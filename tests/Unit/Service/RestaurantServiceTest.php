<?php

namespace App\Tests\Unit\Service;

use App\Dto\RestaurantDto;
use App\Entity\Category;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Exception\RestaurantNotFoundException;
use App\Repository\ImageRepository;
use App\Repository\RestaurantRepository;
use App\Service\RestaurantService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestaurantServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private RestaurantRepository&MockObject $repository;
    private ImageRepository&MockObject $imageRepository;
    private RestaurantService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(RestaurantRepository::class);
        $this->imageRepository = $this->createMock(ImageRepository::class);
        $this->service = new RestaurantService($this->entityManager, $this->repository, $this->imageRepository);
    }

    private function createRestaurant(int $id = 1): Restaurant
    {
        $owner = new User();
        $owner->setEmail('owner@test.com');
        $owner->setFirstName('Test');
        $owner->setLastName('Owner');
        $owner->setPassword('hashed');

        $restaurant = new Restaurant();
        $restaurant->setName('Le Gourmet');
        $restaurant->setAddress('12 rue de Paris, Paris');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('500000.00');
        $restaurant->setCreatedAt(new \DateTimeImmutable('2025-01-01'));
        $restaurant->setOwner($owner);

        $reflection = new \ReflectionClass($restaurant);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($restaurant, $id);

        return $restaurant;
    }

    private function createOwner(): User
    {
        $owner = new User();
        $owner->setEmail('owner@test.com');
        $owner->setFirstName('Test');
        $owner->setLastName('Owner');
        $owner->setPassword('hashed');

        return $owner;
    }

    private function createDto(): RestaurantDto
    {
        return new RestaurantDto(
            name: 'Nouveau Restaurant',
            description: 'Une belle description',
            categories: [],
            address: '5 avenue de la Paix, Lyon',
            latitude: 45.7640,
            longitude: 4.8357,
            capacity: 80,
            askingPrice: '350000.00',
            annualRevenue: '800000.00',
            rent: '4000.00',
            leaseRemaining: 24,
            pappersUrl: null,
            auctionDate: null,
            auctionTime: null,
            auctionLocation: null,
            auctionLocationLat: null,
            auctionLocationLng: null,
            maxCapacity: 100,
            uploadedImages: [],
        );
    }

    public function testFindOrFailReturnsRestaurantWhenFound(): void
    {
        $restaurant = $this->createRestaurant(42);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($restaurant);

        $result = $this->service->findOrFail(42);

        $this->assertSame($restaurant, $result);
    }

    public function testFindOrFailThrowsExceptionWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(RestaurantNotFoundException::class);
        $this->expectExceptionMessage('Le restaurant #999 est introuvable.');

        $this->service->findOrFail(999);
    }

    public function testFindByOwnerDelegatesToRepository(): void
    {
        $owner = $this->createOwner();
        $restaurants = [$this->createRestaurant(1), $this->createRestaurant(2)];

        $this->repository
            ->expects($this->once())
            ->method('findByOwner')
            ->with($owner)
            ->willReturn($restaurants);

        $result = $this->service->findByOwner($owner);

        $this->assertSame($restaurants, $result);
    }

    public function testBuildDtoMapsAllScalarFields(): void
    {
        $restaurant = $this->createRestaurant();
        $restaurant->setDescription('Description test');
        $restaurant->setAnnualRevenue('1000000.00');
        $restaurant->setRent('3500.00');
        $restaurant->setLeaseRemaining(18);
        $restaurant->setPappersUrl('https://pappers.fr/test');
        $restaurant->setTicketPrice('75.00');
        $restaurant->setMaxCapacity(120);

        $dto = $this->service->buildDto($restaurant);

        $this->assertSame('Le Gourmet', $dto->name);
        $this->assertSame('Description test', $dto->description);
        $this->assertSame('12 rue de Paris, Paris', $dto->address);
        $this->assertSame(48.8566, $dto->latitude);
        $this->assertSame(2.3522, $dto->longitude);
        $this->assertSame(50, $dto->capacity);
        $this->assertSame('500000.00', $dto->askingPrice);
        $this->assertSame('1000000.00', $dto->annualRevenue);
        $this->assertSame('3500.00', $dto->rent);
        $this->assertSame(18, $dto->leaseRemaining);
        $this->assertSame('https://pappers.fr/test', $dto->pappersUrl);
        $this->assertSame(120, $dto->maxCapacity);
    }

    public function testBuildDtoCopiesCategoriesAsArray(): void
    {
        $restaurant = $this->createRestaurant();
        $category = new Category();

        $restaurant->addCategory($category);

        $dto = $this->service->buildDto($restaurant);

        $this->assertCount(1, $dto->categories);
        $this->assertContains($category, $dto->categories);
    }

    public function testCreatePersistsAndFlushesRestaurant(): void
    {
        $owner = $this->createOwner();
        $dto = $this->createDto();

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Restaurant::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->service->create($owner, $dto);

        $this->assertInstanceOf(Restaurant::class, $result);
    }

    public function testCreateAssignsOwnerAndCreatedAt(): void
    {
        $owner = $this->createOwner();
        $dto = $this->createDto();

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $before = new \DateTimeImmutable();
        $result = $this->service->create($owner, $dto);
        $after = new \DateTimeImmutable();

        $this->assertSame($owner, $result->getOwner());
        $this->assertNotNull($result->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $result->getCreatedAt());
        $this->assertLessThanOrEqual($after, $result->getCreatedAt());
    }

    public function testCreateAppliesDtoFieldsToRestaurant(): void
    {
        $owner = $this->createOwner();
        $dto = $this->createDto();

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $result = $this->service->create($owner, $dto);

        $this->assertSame('Nouveau Restaurant', $result->getName());
        $this->assertSame('Une belle description', $result->getDescription());
        $this->assertSame('5 avenue de la Paix, Lyon', $result->getAddress());
        $this->assertSame(45.7640, $result->getLatitude());
        $this->assertSame(4.8357, $result->getLongitude());
        $this->assertSame(80, $result->getCapacity());
        $this->assertSame('350000.00', $result->getAskingPrice());
        $this->assertSame('800000.00', $result->getAnnualRevenue());
        $this->assertSame('4000.00', $result->getRent());
        $this->assertSame(24, $result->getLeaseRemaining());
        $this->assertSame(100, $result->getMaxCapacity());
    }

    public function testCreateCalculatesTicketPrice(): void
    {
        $owner = $this->createOwner();
        $dto = $this->createDto();

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $result = $this->service->create($owner, $dto);

        $this->assertSame('200.00', $result->getTicketPrice());
    }

    public function testUpdateFlushesAndSetsUpdatedAt(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $before = new \DateTimeImmutable();
        $this->service->update($restaurant, $dto);
        $after = new \DateTimeImmutable();

        $this->assertNotNull($restaurant->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $restaurant->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $restaurant->getUpdatedAt());
    }

    public function testUpdateAppliesDtoFields(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertSame('Nouveau Restaurant', $restaurant->getName());
        $this->assertSame('Une belle description', $restaurant->getDescription());
        $this->assertSame('5 avenue de la Paix, Lyon', $restaurant->getAddress());
        $this->assertSame(80, $restaurant->getCapacity());
        $this->assertSame('200.00', $restaurant->getTicketPrice());
    }

    public function testUpdateSyncsCategories(): void
    {
        $restaurant = $this->createRestaurant();
        $oldCategory = new Category();
        $newCategory = new Category();
        $restaurant->addCategory($oldCategory);

        $dto = $this->createDto();
        $dto->categories = [$newCategory];

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertFalse($restaurant->getCategories()->contains($oldCategory));
        $this->assertTrue($restaurant->getCategories()->contains($newCategory));
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $restaurant = $this->createRestaurant();

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($restaurant);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->delete($restaurant);
    }

    public function testUpdateCalculatesTicketPriceBelowHundredK(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();
        $dto->askingPrice = '99999.00';

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertSame('50.00', $restaurant->getTicketPrice());
    }

    public function testUpdateCalculatesTicketPriceAtHundredK(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();
        $dto->askingPrice = '100000.00';

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertSame('100.00', $restaurant->getTicketPrice());
    }

    public function testUpdateCalculatesTicketPriceAtThreeHundredK(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();
        $dto->askingPrice = '300000.00';

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertSame('100.00', $restaurant->getTicketPrice());
    }

    public function testUpdateCalculatesTicketPriceAboveFiveHundredK(): void
    {
        $restaurant = $this->createRestaurant();
        $dto = $this->createDto();
        $dto->askingPrice = '500001.00';

        $this->entityManager->method('flush');

        $this->service->update($restaurant, $dto);

        $this->assertSame('350.00', $restaurant->getTicketPrice());
    }
}