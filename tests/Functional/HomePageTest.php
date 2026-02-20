<?php

namespace App\Tests\Functional;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomePageTest extends WebTestCase
{
    private function createMockRestaurant(int $id, string $name): Restaurant
    {
        $owner = new User();
        $owner->setEmail('owner@test.com');
        $owner->setFirstName('Test');
        $owner->setLastName('Owner');
        $owner->setPassword('password');

        $restaurant = new Restaurant();
        $restaurant->setName($name);
        $restaurant->setAddress('123 Test Street');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('100000.00');
        $restaurant->setStatus(StatusRestaurantEnum::PUBLIE);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($restaurant, $id);

        return $restaurant;
    }

    public function testHomePageIsSuccessful(): void
    {
        $client = static::createClient();

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('findFeaturedForHome')
            ->willReturn([
                $this->createMockRestaurant(1, 'Restaurant Test 1'),
                $this->createMockRestaurant(2, 'Restaurant Test 2'),
            ]);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageContainsExpectedContent(): void
    {
        $client = static::createClient();

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('findFeaturedForHome')
            ->willReturn([]);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testHomePageDisplaysRestaurants(): void
    {
        $client = static::createClient();

        $restaurants = [
            $this->createMockRestaurant(1, 'Le Gourmet Parisien'),
            $this->createMockRestaurant(2, 'La Belle Époque'),
            $this->createMockRestaurant(3, 'Chez Marcel'),
        ];

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->expects($this->once())
            ->method('findFeaturedForHome')
            ->with(5)
            ->willReturn($restaurants);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageWithNoRestaurants(): void
    {
        $client = static::createClient();

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('findFeaturedForHome')
            ->willReturn([]);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
